<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        h1 {
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #f0f9ff;
            color: #0c4a6e;
            border-left-color: #0ea5e9;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-outline {
            background: white;
            color: #2563eb;
            border: 1px solid #2563eb;
        }

        .btn-outline:hover {
            background: #eff6ff;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.2s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h2 {
            font-size: 20px;
            color: #1a1a1a;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .close-modal:hover {
            background: #f3f4f6;
            color: #1a1a1a;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            justify-content: flex-end;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f9fafb;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .error-list {
            margin-top: 8px;
            padding-left: 20px;
        }

        .error-list li {
            margin-bottom: 4px;
        }

        .download-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .download-link:hover {
            text-decoration: underline;
        }

        .table-container {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Course Management</h1>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="openModal('uploadModal')">Upload CSV</button>
                <button class="btn btn-primary" onclick="openModal('addModal')">Add Course</button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <strong>Validation Errors:</strong>
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Courses Table -->
        <div class="table-container">
            @if($courses->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 200px;">Course Code</th>
                            <th>Course Name</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td>{{ $course->id }}</td>
                                <td>{{ $course->course_code }}</td>
                                <td>{{ $course->course_name }}</td>
                                <td>
                                    <form action="{{ route('courses.destroy', $course) }}" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to delete this course?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <p>No courses added yet.</p>
                    <p style="margin-top: 8px; font-size: 14px;">Click "Add Course" to get started.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Add Course Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Course</h2>
                <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form action="{{ route('courses.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="course_code">Course Code *</label>
                    <input type="text" id="course_code" name="course_code" 
                           placeholder="e.g., CS101" value="{{ old('course_code') }}" required>
                </div>
                <div class="form-group">
                    <label for="course_name">Course Name *</label>
                    <input type="text" id="course_name" name="course_name" 
                           placeholder="e.g., Computer Science 101" value="{{ old('course_name') }}" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload CSV Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Courses via CSV</h2>
                <button class="close-modal" onclick="closeModal('uploadModal')">&times;</button>
            </div>
            <a href="{{ route('courses.download-template') }}" class="download-link">
                ⬇️ Download CSV Template
            </a>
            <form action="{{ route('courses.upload-csv') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="csv_file">Select CSV File *</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload CSV</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        // Auto-open modal if there are validation errors
        @if($errors->any())
            @if(old('course_code') || old('course_name'))
                openModal('addModal');
            @endif
        @endif
    </script>
</body>
</html>