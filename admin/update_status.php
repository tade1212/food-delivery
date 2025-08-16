<?php
session_start();

// 1. ውሕስነት: ኣድሚን ከምዝኣተወ ምርግጋጽ
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // እንተዘይኣትዩ፡ ናብ login ገጽ ምምላስ
    header('Location: login.php');
    exit;
}

// 2. እቲ ጥርዓን ብ POST method ከምዝመጸ ምርግጋጽ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 3. ዝመጸ ሓበሬታ (order_id and new_status) ከምዘሎ ምርግጋጽ
    if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
        
        // 4. ናይ ዳታቤዝ መራኸቢ ፋይል ምጽዋዕ
        require_once __DIR__ . '/../api/db_connect.php';

        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'];

        // ሓደገኛ ኩነታት ከይኣትዉ ንምክልኻል፡ ዝተፈቕዱ ኩነታት ጥራይ ምቕባል
        $allowed_statuses = ['Pending', 'In Process', 'Ready for Delivery', 'Completed'];

        if ($conn && in_array($new_status, $allowed_statuses)) {
            // 5. ኣብ ዳታቤዝ UPDATE ዝብል ትእዛዝ ምፍጻም
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            
            // እቲ ትእዛዝ ብዓወት እንተተፈጺሙ፡ ናብቲ ናይ ዝርዝር ገጽ ምምላስ
            if ($stmt->execute()) {
                // ነቲ ተጠቃሚ ናብቲ ናይ ዝርዝር ትእዛዝ ገጽ ንምለሶ
                // እቲ ለውጢ ብዓወት ከምዝተፈጸመ ንኽርኢ
                header('Location: view_order.php?id=' . $order_id . '&status=updated');
            } else {
                // ጌጋ እንተ ኣጋጢሙ፡ መልእኽቲ ምርኣይ
                echo "Error updating record: " . $conn->error;
            }
            
            $stmt->close();
            $conn->close();
        } else {
             echo "Invalid status or database connection error.";
        }
    } else {
        echo "Required data is missing.";
    }
} else {
    // እቲ ጥርዓን ብ POST እንተዘይመጺኡ፡ ናብ ዳሽቦርድ ምምላስ
    header('Location: index.php');
    exit;
}
?>