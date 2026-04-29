<?php
// Player self-update page (token-based). Submissions are stored as pending updates until admin approval.

// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Ensure required tables exist (in case this page is deployed first)
$conn->query("CREATE TABLE IF NOT EXISTS player_update_links (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) NOT NULL,
    token VARCHAR(64) NOT NULL DEFAULT '',
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token (token),
    INDEX idx_player_id (player_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS player_pending_updates (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) NOT NULL,
    token VARCHAR(64) NOT NULL DEFAULT '',
    data LONGTEXT NOT NULL,
    img VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_player_id (player_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS player_pending_new_players (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    team VARCHAR(20) NOT NULL DEFAULT 'men',
    data LONGTEXT NOT NULL,
    img VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_team (team)
)");

$conn->query("CREATE TABLE IF NOT EXISTS player_new_application_links (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL,
    team VARCHAR(20) NOT NULL DEFAULT 'men',
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token (token),
    INDEX idx_team (team)
)");

$conn->query("CREATE TABLE IF NOT EXISTS player_update_settings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    team VARCHAR(20) NOT NULL,
    allowed_fields LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_team (team)
)");

$message = '';
$messageClass = '';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if ($token === '' && isset($_POST['token'])) {
    $token = trim($_POST['token']);
}

$teamFilter = isset($_GET['team']) ? trim($_GET['team']) : '';
if ($teamFilter === '' && isset($_POST['team'])) {
    $teamFilter = trim($_POST['team']);
}

$isNewApplication = false;
$newToken = isset($_GET['new_token']) ? trim($_GET['new_token']) : '';
if ($newToken === '' && isset($_POST['new_token'])) {
    $newToken = trim($_POST['new_token']);
}

$newLink = null;
if ($newToken !== '') {
    $stmt = $conn->prepare('SELECT * FROM player_new_application_links WHERE token = ? AND revoked = 0');
    if ($stmt) {
        $stmt->bind_param('s', $newToken);
        $stmt->execute();
        $res = $stmt->get_result();
        $newLink = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
}

if ($newLink) {
    $isNewApplication = true;
}

$playerId = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
if ($playerId <= 0 && isset($_POST['player_id'])) {
    $playerId = intval($_POST['player_id']);
}

$link = null;
$player = null;

if ($token !== '') {
    $stmt = $conn->prepare('SELECT * FROM player_update_links WHERE token = ? AND revoked = 0');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $link = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }

    if ($link) {
        $playerId = (int) $link['player_id'];
    }
}

if ($playerId > 0) {
    $stmt = $conn->prepare('SELECT * FROM players WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $playerId);
        $stmt->execute();
        $res = $stmt->get_result();
        $player = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
}

$allowedFields = [];
if ($player) {
    $teamKey = $player['team'] ?? 'all';
    $stmt = $conn->prepare('SELECT allowed_fields FROM player_update_settings WHERE team = ?');
    if ($stmt) {
        $stmt->bind_param('s', $teamKey);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row && isset($row['allowed_fields'])) {
            $decoded = json_decode($row['allowed_fields'], true);
            if (is_array($decoded)) {
                $allowedFields = $decoded;
            }
        }
    }

    if (empty($allowedFields)) {
        $stmt = $conn->prepare("SELECT allowed_fields FROM player_update_settings WHERE team = 'all'");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && isset($row['allowed_fields'])) {
                $decoded = json_decode($row['allowed_fields'], true);
                if (is_array($decoded)) {
                    $allowedFields = $decoded;
                }
            }
        }
    }
}

if (empty($allowedFields)) {
    $allowedFields = [
        'name','team','role','position_category','special_role','date_of_birth','height','weight','games','points','tries',
        'placeOfBirth','nationality','honours','joined','previousClubs','sponsor','sponsorDesc','img'
    ];
}

$playersForSelect = [];
$teamsForSelect = [
    'men' => "Men's Team",
    'women' => "Women's Team",
    'academy_u18_boys' => 'Academy U18 Boys',
    'academy_u18_girls' => 'Academy U18 Girls',
    'academy_u16_boys' => 'Academy U16 Boys',
    'academy_u16_girls' => 'Academy U16 Girls'
];

$newTeamKey = 'men';
if ($isNewApplication) {
    $newTeamKey = trim((string)($newLink['team'] ?? 'men'));
    if ($newTeamKey === '' || !isset($teamsForSelect[$newTeamKey])) {
        $newTeamKey = 'men';
    }

    $allowedFields = [];

    $stmt = $conn->prepare('SELECT allowed_fields FROM player_update_settings WHERE team = ?');
    if ($stmt) {
        $stmt->bind_param('s', $newTeamKey);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row && isset($row['allowed_fields'])) {
            $decoded = json_decode($row['allowed_fields'], true);
            if (is_array($decoded)) {
                $allowedFields = $decoded;
            }
        }
    }

    if (empty($allowedFields)) {
        $stmt = $conn->prepare("SELECT allowed_fields FROM player_update_settings WHERE team = 'all'");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && isset($row['allowed_fields'])) {
                $decoded = json_decode($row['allowed_fields'], true);
                if (is_array($decoded)) {
                    $allowedFields = $decoded;
                }
            }
        }
    }

    if (empty($allowedFields)) {
        $allowedFields = [
            'name','team','role','position_category','special_role','date_of_birth','height','weight','games','points','tries',
            'placeOfBirth','nationality','honours','joined','previousClubs','sponsor','sponsorDesc','img'
        ];
    }
}

if ($teamFilter !== '' && isset($teamsForSelect[$teamFilter])) {
    $stmt = $conn->prepare('SELECT id, name FROM players WHERE team = ? ORDER BY name');
    if ($stmt) {
        $stmt->bind_param('s', $teamFilter);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $playersForSelect[] = $row;
        }
        $stmt->close();
    }
}

function saveUploadedImage($file, $uploadDir) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $type = @mime_content_type($file['tmp_name']);
    if (!$type || !in_array($type, $allowedTypes)) {
        return null;
    }

    $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
    $ext = $ext ? strtolower($ext) : 'jpg';
    $filename = uniqid('player_pending_') . '.' . $ext;
    $targetPath = rtrim($uploadDir, '/\\') . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isNewApplication) {
    $name = trim($_POST['name'] ?? '');
    $team = $newTeamKey;
    if ($name === '' || $team === '' || !isset($teamsForSelect[$team])) {
        $message = 'Please provide your full name and select a team.';
        $messageClass = 'alert-error';
    } else {
        $candidate = [
            'name' => $name,
            'team' => $team,
            'role' => trim($_POST['role'] ?? ''),
            'position_category' => trim($_POST['position_category'] ?? ''),
            'special_role' => trim($_POST['special_role'] ?? ''),
            'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
            'height' => trim($_POST['height'] ?? ''),
            'weight' => trim($_POST['weight'] ?? ''),
            'games' => trim($_POST['games'] ?? ''),
            'points' => trim($_POST['points'] ?? ''),
            'tries' => trim($_POST['tries'] ?? ''),
            'placeOfBirth' => trim($_POST['placeOfBirth'] ?? ''),
            'nationality' => trim($_POST['nationality'] ?? ''),
            'honours' => trim($_POST['honours'] ?? ''),
            'joined' => trim($_POST['joined'] ?? ''),
            'previousClubs' => trim($_POST['previousClubs'] ?? ''),
            'sponsor' => trim($_POST['sponsor'] ?? ''),
            'sponsorDesc' => trim($_POST['sponsorDesc'] ?? ''),
        ];

        $data = [
            'name' => $name,
            'team' => $team,
        ];
        foreach ($candidate as $k => $v) {
            if ($k === 'name' || $k === 'team') {
                continue;
            }
            if (in_array($k, $allowedFields, true)) {
                $data[$k] = $v;
            }
        }

        $imgPath = null;
        if (in_array('img', $allowedFields, true) && isset($_FILES['player_image']) && ($_FILES['player_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $imgPath = saveUploadedImage($_FILES['player_image'], 'uploads/players/pending/');
            if ($imgPath === null) {
                $message = 'Invalid image upload. Please upload a JPG, PNG, GIF, or WEBP image.';
                $messageClass = 'alert-error';
            }
        }

        if ($message === '') {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $message = 'Failed to prepare submission. Please try again.';
                $messageClass = 'alert-error';
            } else {
                $stmt = $conn->prepare("INSERT INTO player_pending_new_players (name, team, data, img, status) VALUES (?, ?, ?, ?, 'pending')");
                if ($stmt) {
                    $stmt->bind_param('ssss', $name, $team, $json, $imgPath);
                    if ($stmt->execute()) {
                        $message = 'Your information was submitted successfully. It will appear on the website after admin approval.';
                        $messageClass = 'alert-success';
                        if ($newToken !== '') {
                            $stmt2 = $conn->prepare('UPDATE player_new_application_links SET revoked = 1 WHERE token = ?');
                            if ($stmt2) {
                                $stmt2->bind_param('s', $newToken);
                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }
                    } else {
                        $message = 'Failed to submit. Please try again.';
                        $messageClass = 'alert-error';
                    }
                    $stmt->close();
                } else {
                    $message = 'Failed to submit. Please try again.';
                    $messageClass = 'alert-error';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $player) {
    $pid = (int) ($player['id'] ?? 0);

    $candidate = [
        'name' => trim($_POST['name'] ?? ''),
        'team' => trim($_POST['team'] ?? ($player['team'] ?? 'men')),
        'role' => trim($_POST['role'] ?? ''),
        'position_category' => trim($_POST['position_category'] ?? ''),
        'special_role' => trim($_POST['special_role'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'height' => trim($_POST['height'] ?? ''),
        'weight' => trim($_POST['weight'] ?? ''),
        'games' => trim($_POST['games'] ?? ''),
        'points' => trim($_POST['points'] ?? ''),
        'tries' => trim($_POST['tries'] ?? ''),
        'placeOfBirth' => trim($_POST['placeOfBirth'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'honours' => trim($_POST['honours'] ?? ''),
        'joined' => trim($_POST['joined'] ?? ''),
        'previousClubs' => trim($_POST['previousClubs'] ?? ''),
        'sponsor' => trim($_POST['sponsor'] ?? ''),
        'sponsorDesc' => trim($_POST['sponsorDesc'] ?? ''),
    ];

    $data = [];
    foreach ($candidate as $k => $v) {
        if (in_array($k, $allowedFields, true)) {
            $data[$k] = $v;
        }
    }

    $imgPath = null;
    if (in_array('img', $allowedFields, true) && isset($_FILES['player_image']) && ($_FILES['player_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $imgPath = saveUploadedImage($_FILES['player_image'], 'uploads/players/pending/');
        if ($imgPath === null) {
            $message = 'Invalid image upload. Please upload a JPG, PNG, GIF, or WEBP image.';
            $messageClass = 'alert-error';
        }
    }

    if ($message === '') {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $message = 'Failed to prepare submission. Please try again.';
            $messageClass = 'alert-error';
        } else {
            $tok = $token !== '' ? $token : '';
            $stmt = $conn->prepare("INSERT INTO player_pending_updates (player_id, token, data, img, status) VALUES (?, ?, ?, ?, 'pending')");
            if ($stmt) {
                $stmt->bind_param('isss', $pid, $tok, $json, $imgPath);
                if ($stmt->execute()) {
                    $message = 'Your information was submitted successfully. It will appear on the website after admin approval.';
                    $messageClass = 'alert-success';
                } else {
                    $message = 'Failed to submit. Please try again.';
                    $messageClass = 'alert-error';
                }
                $stmt->close();
            } else {
                $message = 'Failed to submit. Please try again.';
                $messageClass = 'alert-error';
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Player Profile Update</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #0a9113;
      --bg: #f5f5f5;
      --card: #fff;
      --text: #222;
      --muted: #666;
      --shadow: 0 10px 20px rgba(0,0,0,0.08);
    }
    body { font-family: Arial, sans-serif; background: var(--bg); color: var(--text); margin: 0; }
    .wrap { max-width: 900px; margin: 0 auto; padding: 24px 16px; }
    .card { background: var(--card); border-radius: 14px; box-shadow: var(--shadow); padding: 22px; }
    h1 { margin: 0 0 8px; }
    p { color: var(--muted); margin-top: 0; }
    .alert { padding: 12px 14px; border-radius: 10px; margin: 14px 0; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    @media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }
    label { display: block; font-weight: 700; margin: 10px 0 6px; }
    input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px; font-size: 15px; }
    textarea { min-height: 90px; resize: vertical; }
    .btn { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: #fff; border: none; padding: 12px 16px; border-radius: 999px; font-weight: 700; cursor: pointer; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Player Profile Update</h1>
      <p>Fill in your details. Your submission will be reviewed by the club admin before it is published.</p>

      <?php if ($message): ?>
        <div class="alert <?php echo htmlspecialchars($messageClass); ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <?php if ($isNewApplication): ?>
        <form method="POST" action="player-update.php?new_token=<?php echo htmlspecialchars($newToken); ?>" enctype="multipart/form-data">
          <input type="hidden" name="new_token" value="<?php echo htmlspecialchars($newToken); ?>" />
          <div class="grid">
            <div>
              <label for="team">Team</label>
              <select id="team" name="team" disabled>
                <?php
                  foreach ($teamsForSelect as $k => $label) {
                    $sel = ($k === $newTeamKey) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($k) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                  }
                ?>
              </select>
            </div>
            <div>
              <label for="name">Full Name</label>
              <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required />
            </div>
          </div>

          <div class="grid">
            <?php if (in_array('role', $allowedFields, true)): ?>
              <div>
                <label for="role">Position/Role</label>
                <input id="role" name="role" type="text" value="<?php echo htmlspecialchars($_POST['role'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('position_category', $allowedFields, true)): ?>
              <div>
                <label for="position_category">Position Category</label>
                <select id="position_category" name="position_category">
                  <?php
                    $pc = $_POST['position_category'] ?? '';
                    $opts = ['', 'Backs', 'Forwards'];
                    foreach ($opts as $o) {
                      $sel = ($o === $pc) ? 'selected' : '';
                      $label = $o === '' ? 'Select' : $o;
                      echo "<option value=\"" . htmlspecialchars($o) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                    }
                  ?>
                </select>
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('special_role', $allowedFields, true)): ?>
              <div>
                <label for="special_role">Special Role</label>
                <select id="special_role" name="special_role">
                  <?php
                    $sr = $_POST['special_role'] ?? '';
                    $opts = ['', 'Captain', 'Vice-Captain'];
                    foreach ($opts as $o) {
                      $sel = ($o === $sr) ? 'selected' : '';
                      $label = $o === '' ? 'Regular Player' : $o;
                      echo "<option value=\"" . htmlspecialchars($o) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                    }
                  ?>
                </select>
              </div>
            <?php endif; ?>

            <?php if (in_array('date_of_birth', $allowedFields, true)): ?>
              <div>
                <label for="date_of_birth">Date of Birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('height', $allowedFields, true)): ?>
              <div>
                <label for="height">Height (cm)</label>
                <input id="height" name="height" type="text" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('weight', $allowedFields, true)): ?>
              <div>
                <label for="weight">Weight (kg)</label>
                <input id="weight" name="weight" type="text" value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('nationality', $allowedFields, true)): ?>
              <div>
                <label for="nationality">Nationality</label>
                <input id="nationality" name="nationality" type="text" value="<?php echo htmlspecialchars($_POST['nationality'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('placeOfBirth', $allowedFields, true)): ?>
              <div>
                <label for="placeOfBirth">Place of Birth</label>
                <input id="placeOfBirth" name="placeOfBirth" type="text" value="<?php echo htmlspecialchars($_POST['placeOfBirth'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <?php if (in_array('honours', $allowedFields, true)): ?>
            <label for="honours">Honours/Achievements</label>
            <textarea id="honours" name="honours"><?php echo htmlspecialchars($_POST['honours'] ?? ''); ?></textarea>
          <?php endif; ?>

          <?php if (in_array('previousClubs', $allowedFields, true)): ?>
            <label for="previousClubs">Previous Clubs</label>
            <textarea id="previousClubs" name="previousClubs"><?php echo htmlspecialchars($_POST['previousClubs'] ?? ''); ?></textarea>
          <?php endif; ?>

          <div class="grid">
            <?php if (in_array('joined', $allowedFields, true)): ?>
              <div>
                <label for="joined">Year Joined</label>
                <input id="joined" name="joined" type="text" value="<?php echo htmlspecialchars($_POST['joined'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('sponsor', $allowedFields, true)): ?>
              <div>
                <label for="sponsor">Sponsor</label>
                <input id="sponsor" name="sponsor" type="text" value="<?php echo htmlspecialchars($_POST['sponsor'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <?php if (in_array('sponsorDesc', $allowedFields, true)): ?>
            <label for="sponsorDesc">Sponsor Description</label>
            <input id="sponsorDesc" name="sponsorDesc" type="text" value="<?php echo htmlspecialchars($_POST['sponsorDesc'] ?? ''); ?>" />
          <?php endif; ?>

          <?php if (in_array('img', $allowedFields, true)): ?>
            <label for="player_image">Profile Picture (optional)</label>
            <input id="player_image" name="player_image" type="file" accept="image/*" />
          <?php endif; ?>

          <div style="margin-top: 16px;">
            <button class="btn" type="submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
          </div>
        </form>
      <?php elseif (!$player): ?>
        <form method="GET" action="player-update.php">
          <div class="grid">
            <div>
              <label for="team_select">Team</label>
              <select id="team_select" name="team">
                <option value="">Select team</option>
                <?php
                  foreach ($teamsForSelect as $k => $label) {
                    $sel = ($k === $teamFilter) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($k) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                  }
                ?>
              </select>
            </div>
            <div>
              <label for="player_select">Player</label>
              <select id="player_select" name="player_id" <?php echo empty($playersForSelect) ? 'disabled' : ''; ?>>
                <option value="">Select player</option>
                <?php
                  foreach ($playersForSelect as $p) {
                    echo "<option value=\"" . (int)$p['id'] . "\">" . htmlspecialchars($p['name']) . "</option>";
                  }
                ?>
              </select>
            </div>
          </div>
          <div style="margin-top: 16px;">
            <button class="btn" type="submit" <?php echo empty($teamFilter) ? 'disabled' : ''; ?>><i class="fas fa-arrow-right"></i> Continue</button>
          </div>
        </form>
      <?php else: ?>
        <form method="POST" action="player-update.php?player_id=<?php echo (int)$player['id']; ?>" enctype="multipart/form-data">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
          <input type="hidden" name="player_id" value="<?php echo (int)$player['id']; ?>" />

          <div class="grid">
            <?php if (in_array('name', $allowedFields, true)): ?>
              <div>
                <label for="name">Full Name</label>
                <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($player['name'] ?? ''); ?>" required />
              </div>
            <?php endif; ?>

            <?php if (in_array('team', $allowedFields, true)): ?>
              <div>
                <label for="team">Team</label>
                <select id="team" name="team">
                  <?php
                    $curTeam = $player['team'] ?? 'men';
                    foreach ($teamsForSelect as $k => $label) {
                      $sel = ($k === $curTeam) ? 'selected' : '';
                      echo "<option value=\"" . htmlspecialchars($k) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                    }
                  ?>
                </select>
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('role', $allowedFields, true)): ?>
              <div>
                <label for="role">Position/Role</label>
                <input id="role" name="role" type="text" value="<?php echo htmlspecialchars($player['role'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('position_category', $allowedFields, true)): ?>
              <div>
                <label for="position_category">Position Category</label>
                <select id="position_category" name="position_category">
                  <?php
                    $pc = $player['position_category'] ?? '';
                    $opts = ['', 'Backs', 'Forwards'];
                    foreach ($opts as $o) {
                      $sel = ($o === $pc) ? 'selected' : '';
                      $label = $o === '' ? 'Select' : $o;
                      echo "<option value=\"" . htmlspecialchars($o) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                    }
                  ?>
                </select>
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('special_role', $allowedFields, true)): ?>
              <div>
                <label for="special_role">Special Role</label>
                <select id="special_role" name="special_role">
                  <?php
                    $sr = $player['special_role'] ?? '';
                    $opts = ['', 'Captain', 'Vice-Captain'];
                    foreach ($opts as $o) {
                      $sel = ($o === $sr) ? 'selected' : '';
                      $label = $o === '' ? 'Regular Player' : $o;
                      echo "<option value=\"" . htmlspecialchars($o) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                    }
                  ?>
                </select>
              </div>
            <?php endif; ?>

            <?php if (in_array('date_of_birth', $allowedFields, true)): ?>
              <div>
                <label for="date_of_birth">Date of Birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="<?php echo htmlspecialchars($player['date_of_birth'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('height', $allowedFields, true)): ?>
              <div>
                <label for="height">Height (cm)</label>
                <input id="height" name="height" type="text" value="<?php echo htmlspecialchars($player['height'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('weight', $allowedFields, true)): ?>
              <div>
                <label for="weight">Weight (kg)</label>
                <input id="weight" name="weight" type="text" value="<?php echo htmlspecialchars($player['weight'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('games', $allowedFields, true)): ?>
              <div>
                <label for="games">Games</label>
                <input id="games" name="games" type="number" min="0" value="<?php echo htmlspecialchars($player['games'] ?? '0'); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('points', $allowedFields, true)): ?>
              <div>
                <label for="points">Points</label>
                <input id="points" name="points" type="number" min="0" value="<?php echo htmlspecialchars($player['points'] ?? '0'); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('tries', $allowedFields, true)): ?>
              <div>
                <label for="tries">Tries</label>
                <input id="tries" name="tries" type="number" min="0" value="<?php echo htmlspecialchars($player['tries'] ?? '0'); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('nationality', $allowedFields, true)): ?>
              <div>
                <label for="nationality">Nationality</label>
                <input id="nationality" name="nationality" type="text" value="<?php echo htmlspecialchars($player['nationality'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <div class="grid">
            <?php if (in_array('placeOfBirth', $allowedFields, true)): ?>
              <div>
                <label for="placeOfBirth">Place of Birth</label>
                <input id="placeOfBirth" name="placeOfBirth" type="text" value="<?php echo htmlspecialchars($player['placeOfBirth'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('joined', $allowedFields, true)): ?>
              <div>
                <label for="joined">Year Joined</label>
                <input id="joined" name="joined" type="text" value="<?php echo htmlspecialchars($player['joined'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <?php if (in_array('honours', $allowedFields, true)): ?>
            <label for="honours">Honours/Achievements</label>
            <textarea id="honours" name="honours"><?php echo htmlspecialchars($player['honours'] ?? ''); ?></textarea>
          <?php endif; ?>

          <?php if (in_array('previousClubs', $allowedFields, true)): ?>
            <label for="previousClubs">Previous Clubs</label>
            <textarea id="previousClubs" name="previousClubs"><?php echo htmlspecialchars($player['previousClubs'] ?? ''); ?></textarea>
          <?php endif; ?>

          <div class="grid">
            <?php if (in_array('sponsor', $allowedFields, true)): ?>
              <div>
                <label for="sponsor">Sponsor</label>
                <input id="sponsor" name="sponsor" type="text" value="<?php echo htmlspecialchars($player['sponsor'] ?? ''); ?>" />
              </div>
            <?php endif; ?>

            <?php if (in_array('sponsorDesc', $allowedFields, true)): ?>
              <div>
                <label for="sponsorDesc">Sponsor Description</label>
                <input id="sponsorDesc" name="sponsorDesc" type="text" value="<?php echo htmlspecialchars($player['sponsorDesc'] ?? ''); ?>" />
              </div>
            <?php endif; ?>
          </div>

          <?php if (in_array('img', $allowedFields, true)): ?>
            <label for="player_image">Profile Picture (optional)</label>
            <input id="player_image" name="player_image" type="file" accept="image/*" />
          <?php endif; ?>

          <div style="margin-top: 16px;">
            <button class="btn" type="submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
