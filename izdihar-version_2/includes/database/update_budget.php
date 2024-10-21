<?php
session_start();
include '../../connect.php';
include '../../includes/database/database.php';
$message = ['status' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_GET['request']) && $_GET['request'] === 'update') {
        global $message;
        global $connect;

        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? intval($data['id']) : 0;

        $budget = fetchBudgetById($id);

        if (!$budget) {
            sendErrorResponse('Budget not found.');
        }

        $monthly_income = isset($budget['monthly_income']) ? floatval($budget['monthly_income']) : 0;
        $total_expenses = isset($budget['total_expenses']) ? floatval($budget['total_expenses']) : 0;
        $selling_goal = isset($budget['selling_goal']) ? floatval($budget['selling_goal']) : 0;
        $goal_date = isset($budget['goal_date']) ? intval($budget['goal_date']) : 0;

        $monthly_payment = $selling_goal / ($goal_date > 0 ? $goal_date : 1);
        $new_selling_goal = $selling_goal - $monthly_payment;
        $new_goal_date = $goal_date - 1; 

        if ($new_selling_goal < 0 || $new_goal_date < 0) {
            sendErrorResponse('Invalid update. Selling goal or goal date cannot be negative.');
        }

        $data = [
            'selling_goal' => $new_selling_goal,
            'goal_date' => $new_goal_date,
        ];

        $row = updateRows('budgets', $data, ["id='$id' AND user_id='{$_SESSION['user_id']}'"]);

        if ($row) {
            $message = [
                'status' => 'success',
                'message' => 'Budget updated successfully.',
                'new_selling_goal' => $new_selling_goal,
                'new_goal_date' => $new_goal_date,
            ];        
        } else {
            sendErrorResponse('Failed to update budget.');
        }

        header('Content-Type: application/json');
        echo json_encode($message);
        exit();
    } else {
        sendErrorResponse('Unknown request type.');
    }
} else {
    sendErrorResponse('Invalid request method.');
}

function fetchBudgetById($id)
{
    global $connect;
    $query = "SELECT * FROM budgets WHERE id = ? AND user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("ii", $id, $_SESSION['user_id']); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null; 
    }
}

function sendErrorResponse($errorMessage)
{
    $message = ['status' => 'error', 'message' => $errorMessage];
    echo json_encode($message);
    exit();
}
