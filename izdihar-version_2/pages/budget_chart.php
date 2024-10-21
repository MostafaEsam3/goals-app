<?php
$user_budget = selectRows('*', 'budgets', "user_id=$user_id", '', '1');
$user_debts = selectRows('*', 'debts', "user_id=$user_id", '', '*');

if (empty($user_budget)) {
    header('Location: ./services.php?page=budget');
    exit();
}

// استخراج البيانات من جدول الميزانية
$monthly_income = $user_budget['monthly_income'];
$total_expenses = json_decode($user_budget['expenses'], true);
$total_expenses_value = array_sum($total_expenses); // إجمالي المصروفات
$net_income = $monthly_income - $total_expenses_value; // صافي الدخل

//  $user_budget['selling_goal'] = 1000;
//  $user_budget['goal_date'] = 2;


// إعداد البيانات للرسم البياني
$months = [];
$savings = [];
$debt_payments = [];

// عدد الأشهر المراد عرضها
$number_of_months = 12;
// echo '<pre>';
// print_r($user_budget);
// echo '</pre>';

// حساب إجمالي المصروفات من الديون
$monthly_debt_payments = [];
foreach ($user_debts as $debt) {
    $monthly_debt_payments[] = $debt['monthly_payment'];
}

// حساب المدفوعات الشهرية من الديون
$total_monthly_debt_payment = array_sum($monthly_debt_payments);

// حساب البيانات لكل شهر
for ($i = 0; $i < $user_budget['goal_date']; $i++) {
    $months[] = 'شهر ' . ($i + 1);

    // حساب صافي الدخل بعد خصم قسط الدين
    $current_net_income = $monthly_income - $total_expenses_value - $total_monthly_debt_payment;

    // حساب المدخرات، إذا كانت أقل من 0 تكون 0
    $current_savings = max(0, $current_net_income);

    // إضافة القيم إلى المصفوفات
    $savings[] = $current_savings; // صافي المدخرات
    // $debt_payments[] = $total_monthly_debt_payment; // إجمالي أقساط الديون ثابت
    $debt_payments[] = $user_budget['selling_goal'] / $user_budget['goal_date']; // إجمالي أقساط الديون ثابت
}

// حساب النسب المئوية
$expense_percentages = [];
foreach ($total_expenses as $category => $amount) {
    $expense_percentages[$category] = ($amount / $monthly_income) * 100;
}

// تحويل البيانات إلى JSON
$expense_data_json = json_encode(array_values($expense_percentages));
$expense_labels_json = json_encode(array_keys($expense_percentages));
?>

<section class="services-budget-chart">
    <label class="error_validation"></label>
    <div class="tbody" >

    <div class="row" style="direction:rtl ; display : flex; align-items:center">
        <div class="animated-div">
<h1> خطه الادخار </h1>
        </div>
        
    <div class="col" style="margin-right:14px;font-weight:bold"><i class="fas fa-chevron-down" ></i></div>
    </div>
    <div class="plan_payment" style="padding:7px">
        <!-- <h2 class="">خطة الدفع 
        </h2> -->
        <ul class="" >
            <?php
            // Calculate how many months are completed and which months are left to pay
            $installment_count = ceil($user_budget['selling_goal'] / $user_budget['goal_date']);
            $current_installments = $user_budget['goal_date'] - $installment_count; //  calculation for completed installments 
            // Loop through the goal months and display each installment
            for ($i = 1; $i <= $user_budget['goal_date']; $i++) { ?>
                <li class="<?= $i <= $current_installments ? 'disabled' : '' ?>"  style="padding:4px;margin-top:13px">
              
                    <span>
                        <?php
                        // Check if it's the last month to calculate the remaining amount
                        if ($i == $user_budget['goal_date']) {
                            $remaining_amount = $user_budget['selling_goal'] % $installment_count;
                            echo $remaining_amount > 0 ? $remaining_amount : $installment_count;
                        } else {
                            echo $installment_count;
                        }
                        ?> ر.س
                    </span>

                    <span>الشهر <?= $i ?>
                        <!-- <i class="fas <?= $i > $current_installments ? 'fa-circle update_goal' : 'fa-check ' ?>"
                            data-order="<?= $i ?>" data-count="<?= $user_budget['goal_date'] ?>"
                            data-goal_id="<?= $user_budget['id'] ?>"></i> -->


                            <i class="fas <?= $i > $current_installments ? 'fa-circle update_goal' : 'fa-check ' ?>"
                                data-order="<?= $i ?>" 
                                data-count="<?= $user_budget['goal_date'] ?>" 
                                data-goal_id="<?= $user_budget['id'] ?>"></i>
                    </span>
                </li>
 
            <?php } ?>
        </ul>
    </div>
  </div>

    <h2>تحـــديد الهــدف</h2>

    <div class="actions">
        <form action="./services.php?page=budget" method="POST" id="edit_budget_form">
            <button type="submit" class="btn btn-dark active" name="edit_budget" id="edit_budget">تعديل</button>
        </form>
        <button class="btn btn-dark active" id="delete_budget" data-id="<?= $user_budget['id'] ?? '' ?>">حذف</button>
    </div>

    <div class="cards">
        <div class="card">
            <h2>الدخل الشهري</h2>
            <h3><?= number_format($user_budget['monthly_income']) . 'ر.س' ?></h3>
        </div>
        <div class="card">
            <h2>اجمالي المصروفات</h2>
            <h3><?= number_format($total_expenses_value) . 'ر.س' ?></h3>
        </div>
        <div class="card">
            <h2>مبلغ الهدف</h2>
            <h3><?= number_format($user_budget['selling_goal']) . 'ر.س' ?></h3>
        </div>
        <div class="card">
            <h2>مدة الهدف</h2>
            <h3 class="col goal_<?= $user_budget['id'] ?>"><?= $user_budget['goal_date'] . 'شهر' ?></h3>
        </div>
    </div>
    <div class="charts">
        <div id="my_chart_line"></div>
        <div id="my_chart_donut"></div>
    </div>
</section>

<!-- الميزانية -->
<script>
    const months = <?= json_encode($months) ?>;
    const savings = <?= json_encode($savings) ?>;
    const debtPayments = <?= json_encode($debt_payments) ?>;

    const options = {
        chart: {
            type: 'line',
            height: 350
        },
        series: [{
            name: 'صافي المال بعد المصروفات والديون',
            data: savings,
            color: '#435760',
        },
        {
            name: 'القسط الشهري',
            data: debtPayments,
            color: '#81A9B9',
        }
        ],

        xaxis: {
            categories: months,
            title: {
                text: 'الشهور'
            }
        },
        yaxis: {
            title: {
                text: 'القيمة (ر.س) في الشهر'
            },
            labels: {
                formatter: function (value) {
                    return value.toFixed(0);
                }
            }
        },
        markers: {
            size: 5,
        },
        tooltip: {
            shared: true,
            intersect: false
        },
        title: {
            text: 'مخطط صافي المدخرات وأقساط الديون',
            align: 'left',
            style: {
                fontSize: '16px',
                color: '#435760'
            }
        }
    };

    const my_chart_line = new ApexCharts(document.querySelector("#my_chart_line"), options);
    my_chart_line.render();
</script>

<!-- إعداد مخطط الديون -->
<script>
    var debtData = <?= $expense_data_json ?>;
    var debtLabels = <?= $expense_labels_json ?>;

    var debtOptions = {
        chart: {
            type: 'donut',
            height: 350
        },
        series: debtData,
        labels: debtLabels,
        title: {
            text: 'توزيع المصروفات',
            align: 'left'
        },
        colors: ['#4e5389', '#3b428e', '#5D64AE', '#4c54a6'],
    };

    var my_chart_donut = new ApexCharts(document.querySelector("#my_chart_donut"), debtOptions);
    my_chart_donut.render();
</script>

<script src="<?= $js . 'budget.js' ?>"></script>