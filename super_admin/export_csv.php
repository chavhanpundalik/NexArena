<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Get export type
$exportType = isset($_GET['type']) ? $_GET['type'] : 'leaderboard';

// Set filename
$filename = $exportType . '_export_' . date('Y-m-d') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Export based on type
switch ($exportType) {
    case 'leaderboard':
        exportLeaderboard($conn, $output);
        break;
    case 'teams':
        exportTeams($conn, $output);
        break;
    case 'events':
        exportEvents($conn, $output);
        break;
    case 'users':
        exportUsers($conn, $output);
        break;
    default:
        exportLeaderboard($conn, $output);
        break;
}

fclose($output);
exit();

function exportLeaderboard($conn, $output) {
    // Get leaderboard data with proper joins
    $sql = "SELECT 
                lb.leaderboard_id,
                lb.user_id,
                lb.team_id,
                lb.event_id,
                lb.points,
                lb.wins,
                lb.losses,
                lb.draws,
                lb.matches_played,
                lb.rank_position,
                lb.created_at,
                lb.updated_at,
                u.username AS user_name,
                u.email AS user_email,
                t.team_name,
                e.event_name,
                e.event_date
            FROM leaderboard lb
            LEFT JOIN users u ON lb.user_id = u.user_id
            LEFT JOIN teams t ON lb.team_id = t.team_id
            LEFT JOIN events e ON lb.event_id = e.event_id
            ORDER BY lb.points DESC, lb.wins DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        // Fallback query if joins fail
        $sql = "SELECT * FROM leaderboard ORDER BY points DESC, wins DESC";
        $result = $conn->query($sql);
    }
    
    if ($result && $result->num_rows > 0) {
        // Write headers
        fputcsv($output, [
            'Rank',
            'User ID',
            'Username',
            'Email',
            'Team ID',
            'Team Name',
            'Event ID',
            'Event Name',
            'Event Date',
            'Points',
            'Wins',
            'Losses',
            'Draws',
            'Matches Played',
            'Rank Position',
            'Created At',
            'Updated At'
        ]);
        
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $rank++,
                $row['user_id'],
                $row['user_name'] ?? 'N/A',
                $row['user_email'] ?? 'N/A',
                $row['team_id'] ?? 'N/A',
                $row['team_name'] ?? 'N/A',
                $row['event_id'] ?? 'N/A',
                $row['event_name'] ?? 'N/A',
                $row['event_date'] ?? 'N/A',
                $row['points'] ?? 0,
                $row['wins'] ?? 0,
                $row['losses'] ?? 0,
                $row['draws'] ?? 0,
                $row['matches_played'] ?? 0,
                $row['rank_position'] ?? 'N/A',
                $row['created_at'] ?? 'N/A',
                $row['updated_at'] ?? 'N/A'
            ]);
        }
    } else {
        // No data, write empty message
        fputcsv($output, ['No leaderboard data available']);
    }
}

function exportTeams($conn, $output) {
    $sql = "SELECT 
                team_id,
                team_name,
                sport,
                game,
                description,
                captain_id,
                status,
                max_players,
                region,
                is_private,
                created_at,
                updated_at
            FROM teams 
            ORDER BY team_name";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        // Write headers
        fputcsv($output, [
            'Team ID',
            'Team Name',
            'Sport',
            'Game',
            'Description',
            'Captain ID',
            'Status',
            'Max Players',
            'Region',
            'Is Private',
            'Created At',
            'Updated At'
        ]);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['team_id'],
                $row['team_name'],
                $row['sport'] ?? 'N/A',
                $row['game'] ?? 'N/A',
                $row['description'] ?? 'N/A',
                $row['captain_id'] ?? 'N/A',
                $row['status'] ?? 'active',
                $row['max_players'] ?? 11,
                $row['region'] ?? 'N/A',
                $row['is_private'] ? 'Yes' : 'No',
                $row['created_at'] ?? 'N/A',
                $row['updated_at'] ?? 'N/A'
            ]);
        }
    } else {
        fputcsv($output, ['No teams data available']);
    }
}

function exportEvents($conn, $output) {
    $sql = "SELECT 
                event_id,
                event_name,
                sport,
                event_date,
                location,
                description,
                status,
                max_participants,
                current_participants,
                created_at,
                updated_at
            FROM events 
            ORDER BY event_date DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        // Write headers
        fputcsv($output, [
            'Event ID',
            'Event Name',
            'Sport',
            'Event Date',
            'Location',
            'Description',
            'Status',
            'Max Participants',
            'Current Participants',
            'Created At',
            'Updated At'
        ]);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['event_id'],
                $row['event_name'],
                $row['sport'] ?? 'N/A',
                $row['event_date'] ?? 'N/A',
                $row['location'] ?? 'N/A',
                $row['description'] ?? 'N/A',
                $row['status'] ?? 'N/A',
                $row['max_participants'] ?? 0,
                $row['current_participants'] ?? 0,
                $row['created_at'] ?? 'N/A',
                $row['updated_at'] ?? 'N/A'
            ]);
        }
    } else {
        fputcsv($output, ['No events data available']);
    }
}

function exportUsers($conn, $output) {
    $sql = "SELECT 
                user_id,
                username,
                email,
                full_name,
                role,
                status,
                created_at,
                updated_at
            FROM users 
            ORDER BY username";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        // Write headers
        fputcsv($output, [
            'User ID',
            'Username',
            'Email',
            'Full Name',
            'Role',
            'Status',
            'Created At',
            'Updated At'
        ]);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['email'],
                $row['full_name'] ?? 'N/A',
                $row['role'] ?? 'user',
                $row['status'] ?? 'active',
                $row['created_at'] ?? 'N/A',
                $row['updated_at'] ?? 'N/A'
            ]);
        }
    } else {
        fputcsv($output, ['No users data available']);
    }
}
?>