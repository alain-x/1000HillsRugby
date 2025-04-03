<?php
// Database connection
$conn = new mysqli('localhost', 'root', '1234', '1000hills_rugby');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_event_id'])) {
        // Handle event update
        $id = $conn->real_escape_string($_POST['edit_event_id']);
        $title = $conn->real_escape_string($_POST['title']);
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $event_time = $conn->real_escape_string($_POST['event_time']);
        $location = $conn->real_escape_string($_POST['location']);
        $participants = $conn->real_escape_string($_POST['participants']);
        $frequency = $conn->real_escape_string($_POST['frequency']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "UPDATE events SET 
                title = '$title',
                event_date = '$event_date',
                event_time = '$event_time',
                location = '$location',
                participants = '$participants',
                frequency = '$frequency',
                description = '$description'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Event updated successfully!";
        } else {
            $error = "Error updating event: " . $conn->error;
        }
    } else {
        // Handle new event creation
        $title = $conn->real_escape_string($_POST['title']);
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $event_time = $conn->real_escape_string($_POST['event_time']);
        $location = $conn->real_escape_string($_POST['location']);
        $participants = $conn->real_escape_string($_POST['participants']);
        $frequency = $conn->real_escape_string($_POST['frequency']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "INSERT INTO events (title, event_date, event_time, location, participants, frequency, description) 
                VALUES ('$title', '$event_date', '$event_time', '$location', '$participants', '$frequency', '$description')";
        
        if ($conn->query($sql)) {
            $message = "Event added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Handle weekly schedule submission
if (isset($_POST['add_schedule'])) {
    if (isset($_POST['edit_schedule_id'])) {
        // Handle schedule update
        $id = $conn->real_escape_string($_POST['edit_schedule_id']);
        $day = $conn->real_escape_string($_POST['day']);
        $time_range = $conn->real_escape_string($_POST['time_range']);
        $location = $conn->real_escape_string($_POST['schedule_location']);
        $activity = $conn->real_escape_string($_POST['activity']);
        
        $sql = "UPDATE weekly_schedule SET 
                day = '$day',
                time_range = '$time_range',
                location = '$location',
                activity = '$activity'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $schedule_message = "Schedule updated successfully!";
        } else {
            $schedule_error = "Error updating schedule: " . $conn->error;
        }
    } else {
        // Handle new schedule creation
        $day = $conn->real_escape_string($_POST['day']);
        $time_range = $conn->real_escape_string($_POST['time_range']);
        $location = $conn->real_escape_string($_POST['schedule_location']);
        $activity = $conn->real_escape_string($_POST['activity']);
        
        $sql = "INSERT INTO weekly_schedule (day, time_range, location, activity) 
                VALUES ('$day', '$time_range', '$location', '$activity')";
        
        if ($conn->query($sql)) {
            $schedule_message = "Schedule added successfully!";
        } else {
            $schedule_error = "Error: " . $conn->error;
        }
    }
}

// Handle delete requests
if (isset($_GET['delete_event'])) {
    $id = $conn->real_escape_string($_GET['delete_event']);
    $sql = "DELETE FROM events WHERE id = $id";
    if ($conn->query($sql)) {
        $message = "Event deleted successfully!";
    } else {
        $error = "Error deleting event: " . $conn->error;
    }
}

if (isset($_GET['delete_schedule'])) {
    $id = $conn->real_escape_string($_GET['delete_schedule']);
    $sql = "DELETE FROM weekly_schedule WHERE id = $id";
    if ($conn->query($sql)) {
        $schedule_message = "Schedule deleted successfully!";
    } else {
        $schedule_error = "Error deleting schedule: " . $conn->error;
    }
}

// Fetch events for display
$events = [];
$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Fetch weekly schedule for display
$schedules = [];
$result = $conn->query("SELECT * FROM weekly_schedule ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Check if we're editing an event
$editing_event = false;
$event_to_edit = null;
if (isset($_GET['edit_event'])) {
    $id = $conn->real_escape_string($_GET['edit_event']);
    $result = $conn->query("SELECT * FROM events WHERE id = $id");
    if ($result->num_rows > 0) {
        $editing_event = true;
        $event_to_edit = $result->fetch_assoc();
    }
}

// Check if we're editing a schedule
$editing_schedule = false;
$schedule_to_edit = null;
if (isset($_GET['edit_schedule'])) {
    $id = $conn->real_escape_string($_GET['edit_schedule']);
    $result = $conn->query("SELECT * FROM weekly_schedule WHERE id = $id");
    if ($result->num_rows > 0) {
        $editing_schedule = true;
        $schedule_to_edit = $result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        function confirmDelete(type, id) {
            if (confirm(`Are you sure you want to delete this ${type}?`)) {
                window.location.href = `?delete_${type}=${id}`;
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8"><?php echo $editing_event ? 'Edit Event' : 'Upload New Event'; ?></h1>
        
        <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <?php if ($editing_event): ?>
                <input type="hidden" name="edit_event_id" value="<?php echo $event_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="title">Event Title</label>
                <input type="text" id="title" name="title" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                       value="<?php echo $editing_event ? htmlspecialchars($event_to_edit['title']) : ''; ?>">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-bold mb-2" for="event_date">Date</label>
                    <input type="date" id="event_date" name="event_date" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                           value="<?php echo $editing_event ? $event_to_edit['event_date'] : ''; ?>">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2" for="event_time">Time</label>
                    <input type="time" id="event_time" name="event_time" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                           value="<?php echo $editing_event ? $event_to_edit['event_time'] : ''; ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="location">Location</label>
                <input type="text" id="location" name="location" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                       value="<?php echo $editing_event ? htmlspecialchars($event_to_edit['location']) : ''; ?>">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="participants">Participants</label>
                <input type="text" id="participants" name="participants" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                       value="<?php echo $editing_event ? htmlspecialchars($event_to_edit['participants']) : ''; ?>">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="frequency">Frequency</label>
                <select id="frequency" name="frequency" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Select frequency</option>
                    <option value="One-time" <?php echo ($editing_event && $event_to_edit['frequency'] == 'One-time') ? 'selected' : ''; ?>>One-time</option>
                    <option value="Weekly" <?php echo ($editing_event && $event_to_edit['frequency'] == 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                    <option value="Monthly" <?php echo ($editing_event && $event_to_edit['frequency'] == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                    <option value="Quarterly" <?php echo ($editing_event && $event_to_edit['frequency'] == 'Quarterly') ? 'selected' : ''; ?>>Quarterly</option>
                    <option value="Annually" <?php echo ($editing_event && $event_to_edit['frequency'] == 'Annually') ? 'selected' : ''; ?>>Annually</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2" for="description">Description</label>
                <textarea id="description" name="description" rows="4" required 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?php echo $editing_event ? htmlspecialchars($event_to_edit['description']) : ''; ?></textarea>
            </div>
            
            <div class="flex justify-center">
                <button type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150">
                    <?php echo $editing_event ? 'Update Event' : 'Upload Event'; ?>
                </button>
                <?php if ($editing_event): ?>
                    <a href="uploadevent.php" class="ml-4 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-150">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <hr class="my-8 border-gray-300">
        
        <h2 class="text-2xl font-bold text-center mb-6"><?php echo $editing_schedule ? 'Edit Weekly Schedule' : 'Add Weekly Schedule'; ?></h2>
        
        <?php if (isset($schedule_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $schedule_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($schedule_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $schedule_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <input type="hidden" name="add_schedule" value="1">
            <?php if ($editing_schedule): ?>
                <input type="hidden" name="edit_schedule_id" value="<?php echo $schedule_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="day">Day</label>
                <select id="day" name="day" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Select day</option>
                    <option value="Monday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                    <option value="Tuesday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                    <option value="Wednesday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                    <option value="Thursday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                    <option value="Friday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                    <option value="Saturday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                    <option value="Sunday" <?php echo ($editing_schedule && $schedule_to_edit['day'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="time_range">Time Range</label>
                <input type="text" id="time_range" name="time_range" placeholder="e.g., 2:10 PM - 05:30 PM" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                       value="<?php echo $editing_schedule ? htmlspecialchars($schedule_to_edit['time_range']) : ''; ?>">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="schedule_location">Location</label>
                <input type="text" id="schedule_location" name="schedule_location" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                       value="<?php echo $editing_schedule ? htmlspecialchars($schedule_to_edit['location']) : ''; ?>">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2" for="activity">Activity</label>
                <input type="text" id="activity" name="activity" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                       value="<?php echo $editing_schedule ? htmlspecialchars($schedule_to_edit['activity']) : ''; ?>">
            </div>
            
            <div class="flex justify-center">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150">
                    <?php echo $editing_schedule ? 'Update Schedule' : 'Add Schedule'; ?>
                </button>
                <?php if ($editing_schedule): ?>
                    <a href="uploadevent.php" class="ml-4 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-150">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <hr class="my-8 border-gray-300">
        
        <!-- Display Existing Events -->
        <h2 class="text-2xl font-bold text-center mb-6">Existing Events</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($event['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $event['event_date']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($event['location']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="?edit_event=<?php echo $event['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="#" onclick="confirmDelete('event', <?php echo $event['id']; ?>)" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Display Existing Schedules -->
        <h2 class="text-2xl font-bold text-center mb-6 mt-8">Existing Weekly Schedules</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($schedule['day']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($schedule['time_range']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($schedule['activity']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="?edit_schedule=<?php echo $schedule['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="#" onclick="confirmDelete('schedule', <?php echo $schedule['id']; ?>)" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>