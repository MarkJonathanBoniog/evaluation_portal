<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    // Display all courses in the index page
    public function index()
    {
        $courses = Course::orderBy('id', 'desc')->get();
        return view('courses.index', compact('courses'));
    }

    // Store a single course manually added by the user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_code' => 'required|string|unique:courses,course_code|max:255',
            'course_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Course::create($request->only(['course_code', 'course_name']));

        return redirect()->route('courses.index')
            ->with('success', 'Course added successfully!');
    }

    // Generate and download CSV template dynamically (no physical file stored)
    public function downloadTemplate()
    {
        // Set filename for download
        $filename = 'course_template.csv';

        // Set headers to force browser to download as CSV file
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        // Create callback function that generates CSV content on-the-fly
        $callback = function () {
            // Open output stream for writing
            $file = fopen('php://output', 'w');

            // Write header row (column names) - NO ID COLUMN
            fputcsv($file, ['course_code', 'course_name']);

            // Write example rows to show users the correct format
            fputcsv($file, ['CS101', 'Computer Science 101']);
            fputcsv($file, ['MATH201', 'Advanced Mathematics']);

            // Close the stream
            fclose($file);
        };

        // Stream the CSV file to browser for download
        return response()->stream($callback, 200, $headers);
    }

    // Process uploaded CSV file and import courses
    public function uploadCsv(Request $request)
    {
        // STEP 1: Validate that a CSV file was uploaded
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        // STEP 2: Get the uploaded file and read its contents
        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // Read CSV file and convert each line to an array
        // Result: [['course_code', 'course_name'], ['CS101', 'Computer Science'], ...]
        $data = array_map('str_getcsv', file($path));

        // STEP 3: Extract and remove the header row from data
        $header = array_shift($data);
        // Now $header = ['course_code', 'course_name']
        // And $data only contains actual course rows

        // STEP 4: Validate that CSV has required columns
        if (! in_array('course_code', $header) || ! in_array('course_name', $header)) {
            return redirect()->back()
                ->with('error', 'Invalid CSV format. Please use the template.');
        }

        // STEP 5: Find the position (index) of each column in the CSV
        // This allows columns to be in any order (flexible)
        $courseCodeIndex = array_search('course_code', $header); // e.g., 0
        $courseNameIndex = array_search('course_name', $header); // e.g., 1

        // Initialize counters for success/error tracking
        $addedCount = 0;
        $errors = [];

        // STEP 6: Loop through each data row and process it
        foreach ($data as $index => $row) {
            // Skip completely empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Extract values from the correct column positions
            $courseCode = trim($row[$courseCodeIndex] ?? '');
            $courseName = trim($row[$courseNameIndex] ?? '');

            // VALIDATION 1: Check if required fields are present
            if (empty($courseCode) || empty($courseName)) {
                // ($index + 2) because: +1 for 0-based array, +1 for header row
                $errors[] = "Row ".($index + 2).": Missing course code or name";
                continue; // Skip this row and move to next
            }

            // VALIDATION 2: Check if course code already exists in database
            if (Course::where('course_code', $courseCode)->exists()) {
                $errors[] = "Row ".($index + 2).": Course code '$courseCode' already exists";
                continue; // Skip this row and move to next
            }

            // STEP 7: Create course in database (ID is auto-generated by Laravel)
            Course::create([
                'course_code' => $courseCode,
                'course_name' => $courseName,
            ]);

            // Increment success counter
            $addedCount++;
        }

        // STEP 8: Build feedback message with results
        $message = "$addedCount course(s) added successfully!";

        // If there were any errors, append them to the message
        if (! empty($errors)) {
            $message .= " ".count($errors)." error(s): ".implode(', ', $errors);
        }

        // STEP 9: Redirect back with success message
        return redirect()->route('courses.index')
            ->with('success', $message);
    }

    // Delete a course
    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully!');
    }
}