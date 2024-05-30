<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeeExport;





class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pageTitle = 'Employee List';

        return view('employee.index', compact('pageTitle'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pageTitle = 'Create Employee';

        //raw SQL query
        $positions = DB::select('select * from positions');

        return view('employee.create', compact('pageTitle', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];
        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
            'cv' => 'required|mimes:pdf', // Tambahkan validasi untuk file PDF
        ], $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Enkripsi nama file
        $cvFileName = $request->file('cv')->hashName();

        // Simpan file PDF di dalam direktori storage/app/public/files dengan nama terenkripsi
        $cvPath = $request->file('cv')->storeAs('public/files', $cvFileName);

        // Insert data ke database
        DB::table('employees')->insert([
            'firstname' => $request->firstName,
            'lastname' => $request->lastName,
            'email' => $request->email,
            'age' => $request->age,
            'position_id' => $request->position,
            'original_filename' => $request->file('cv')->getClientOriginalName(), // Simpan nama file asli
            'encrypted_filename' => $cvFileName, // Simpan nama file terenkripsi
        ]);

        Alert::success('Added Successfully', 'Employee Data Added Successfully.');

        return redirect()->route('employees.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';

        // // raw SQL query
        // $employee = collect(DB::select('
        // select *, employees.id as employee_id, positions.name as
        // position_name
        // from employees
        // left join positions on employees.position_id = positions.id
        // where employees.id = ?
        // ', [$id]))->first();

        $employee = Employee::with('position')->find($id);

        // Then pass the $employee object to your view
        return view('employee.show', compact('employee', 'pageTitle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageTitle = 'Edit Employee';

        // // raw SQL query
        // $employee = collect(DB::select('
        // select *, employees.id as employee_id, positions.name as
        // position_name
        // from employees
        // left join positions on employees.position_id = positions.id
        // where employees.id = ?
        // ', [$id]))->first();

        // // raw SQL query
        // $positions = DB::select('select * from positions');

        // Query builder
        $employee = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'employees.id as employee_id', 'positions.name as position_name')
            ->where('employees.id', $id)
            ->first();

        // Query builder
        $positions = DB::table('positions')->get();


        return view('employee.edit', ['employee' => $employee, 'positions' => $positions, 'pageTitle' => $pageTitle]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka',
            'mimes' => 'File harus berupa PDF'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
            'position' => 'required',
            'cv' => 'nullable|mimes:pdf' // Tambahkan validasi untuk file PDF
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Dapatkan data karyawan
        $employee = Employee::find($id);

        if ($request->hasFile('cv')) {
            // Hapus file CV yang lama jika ada
            if ($employee->encrypted_filename) {
                Storage::delete('public/files/' . $employee->encrypted_filename);
            }

            // Simpan file CV yang baru
            $cvFile = $request->file('cv');
            $cvFileName = $cvFile->getClientOriginalName();
            $encryptedFileName = Str::random(40) . '.' . $cvFile->getClientOriginalExtension();
            $cvFile->storeAs('public/files', $encryptedFileName);

            // Perbarui data karyawan dengan file CV yang baru
            $employee->original_filename = $cvFileName;
            $employee->encrypted_filename = $encryptedFileName;
        }

        // Perbarui data karyawan yang lain
        $employee->firstname = $request->firstName;
        $employee->lastname = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;

        $employee->save();

        Alert::success('Changed Successfully', 'Employee Data Changed Successfully.');

        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return redirect()->route('employees.index')->withErrors(['message' => 'Employee not found']);
        }

        // Hapus file CV jika ada
        if ($employee->encrypted_filename) {
            Storage::delete('public/files/' . $employee->encrypted_filename);
        }

        // Hapus data employee dari database
        $employee->delete();

        Alert::success('Deleted Successfully', 'Employee Data Deleted Successfully.');

        return redirect()->route('employees.index');
    }

    public function downloadFile($employeeId)
    {
        $employee = Employee::find($employeeId);
        $encryptedFilename = 'public/files/' . $employee->encrypted_filename;
        $downloadFilename =
            Str::lower($employee->firstname . '_' . $employee->lastname . '_cv.pdf');

        if (Storage::exists($encryptedFilename)) {
            return Storage::download($encryptedFilename, $downloadFilename);
        }
    }

    public function getData(Request $request)
    {
        $employees = Employee::with('position');
        if ($request->ajax()) {
            return datatables()->of($employees)->addIndexColumn()->addColumn('actions', function ($employee) {
                return view('employee.actions', compact('employee'));
            })->toJson();
        }
    }


    public function exportExcel()
    {
        return Excel::download(new EmployeeExport, 'employees.xlsx');
    }
}
