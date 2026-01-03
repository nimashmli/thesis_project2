<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// پیدا کردن مسیر results
function findResultsPath() {
    $possiblePaths = [
        __DIR__ . '/../result',
        __DIR__ . '/result',
        __DIR__ . '/../../result',
        'C:/Users/USER/Desktop/thesis Project/result',
        dirname(__DIR__) . '/result'
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_dir($path)) {
            return $path;
        }
    }
    
    return null;
}

$resultsPath = findResultsPath();

if (!$resultsPath) {
    echo json_encode(['error' => 'پوشه results پیدا نشد!']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getModels':
        getModels($resultsPath);
        break;
    case 'getRuns':
        getRuns($resultsPath);
        break;
    case 'getSubjects':
        getSubjects($resultsPath);
        break;
    case 'getData':
        getData($resultsPath);
        break;
    case 'getSubjectData':
        getSubjectData($resultsPath);
        break;
    default:
        echo json_encode(['error' => 'Action نامعتبر']);
}

function getModels($resultsPath) {
    $models = [];
    if (is_dir($resultsPath)) {
        $items = scandir($resultsPath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($resultsPath . '/' . $item)) {
                $models[] = $item;
            }
        }
    }
    sort($models);
    echo json_encode(['models' => $models]);
}

function getRuns($resultsPath) {
    $model = $_GET['model'] ?? '';
    $validation = $_GET['validation'] ?? '';
    
    if (!$model || !$validation) {
        echo json_encode(['error' => 'پارامترهای لازم ارسال نشد']);
        return;
    }
    
    $runs = [];
    $validationPath = $resultsPath . '/' . $model . '/' . $validation;
    
    if (is_dir($validationPath)) {
        $items = scandir($validationPath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($validationPath . '/' . $item) && strpos($item, 'run') === 0) {
                $runs[] = $item;
            }
        }
    }
    
    // مرتب‌سازی بر اساس شماره run
    usort($runs, function($a, $b) {
        $numA = intval(str_replace('run', '', $a));
        $numB = intval(str_replace('run', '', $b));
        return $numA - $numB;
    });
    
    echo json_encode(['runs' => $runs]);
}

function getSubjects($resultsPath) {
    $model = $_GET['model'] ?? '';
    $validation = $_GET['validation'] ?? '';
    $run = $_GET['run'] ?? '';
    
    if (!$model || !$validation || !$run) {
        echo json_encode(['error' => 'پارامترهای لازم ارسال نشد']);
        return;
    }
    
    $subjects = [];
    $runPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run;
    
    if (is_dir($runPath)) {
        $items = scandir($runPath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($runPath . '/' . $item) && strpos($item, 'subject_') === 0) {
                $subjects[] = $item;
            }
        }
    }
    
    // مرتب‌سازی بر اساس شماره subject
    usort($subjects, function($a, $b) {
        $numA = intval(str_replace('subject_', '', $a));
        $numB = intval(str_replace('subject_', '', $b));
        return $numA - $numB;
    });
    
    echo json_encode(['subjects' => $subjects]);
}

function getData($resultsPath) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $model = $input['model'] ?? '';
    $validation = $input['validation'] ?? '';
    $run = $input['run'] ?? '';
    $subject = $input['subject'] ?? '';
    $fold = $input['fold'] ?? '';
    $dataType = $input['dataType'] ?? 'train'; // train or test
    $chartType = $input['chartType'] ?? 'accuracy'; // accuracy or loss
    
    if (!$model || !$validation || !$run || $fold === '') {
        echo json_encode(['success' => false, 'error' => 'پارامترهای لازم ارسال نشد']);
        return;
    }
    
    // اگر fold برابر "average" باشد، میانگین 5 fold را محاسبه کن
    if ($fold === 'average') {
        $allValues = [];
        $maxLength = 0;
        
        for ($i = 0; $i <= 4; $i++) {
            $logPath = '';
            if ($validation === 'subject_dependent') {
                if (!$subject) {
                    echo json_encode(['success' => false, 'error' => 'Subject لازم است']);
                    return;
                }
                $logPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/' . $subject . '/log_fold' . $i . '.json';
            } else {
                $logPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/fold_' . $i . '/log_fold' . $i . '.json';
                if (!file_exists($logPath)) {
                    $altPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/fold_' . $i . '/training_log.json';
                    if (file_exists($altPath)) {
                        $logPath = $altPath;
                    }
                }
            }
            
            if (file_exists($logPath)) {
                $data = json_decode(file_get_contents($logPath), true);
                if ($data) {
                    $key = $chartType === 'accuracy' ? ($dataType === 'train' ? 'train_acc' : 'val_acc') : ($dataType === 'train' ? 'train_loss' : 'val_loss');
                    if (isset($data[$key])) {
                        $values = array_map('floatval', $data[$key]);
                        $allValues[] = $values;
                        $maxLength = max($maxLength, count($values));
                    }
                }
            }
        }
        
        if (empty($allValues)) {
            echo json_encode(['success' => false, 'error' => 'هیچ فایل log برای محاسبه میانگین پیدا نشد']);
            return;
        }
        
        // محاسبه میانگین
        $averageValues = [];
        for ($epoch = 0; $epoch < $maxLength; $epoch++) {
            $sum = 0;
            $count = 0;
            foreach ($allValues as $values) {
                if (isset($values[$epoch])) {
                    $sum += $values[$epoch];
                    $count++;
                }
            }
            $averageValues[] = $count > 0 ? $sum / $count : 0;
        }
        
        echo json_encode([
            'success' => true,
            'values' => $averageValues,
            'epochs' => range(1, count($averageValues))
        ]);
        return;
    }
    
    // حالت عادی (fold خاص)
    // تعیین مسیر فایل log
    if ($validation === 'subject_dependent') {
        if (!$subject) {
            echo json_encode(['success' => false, 'error' => 'Subject لازم است']);
            return;
        }
        $logPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/' . $subject . '/log_fold' . $fold . '.json';
    } else {
        // برای subject_independent، مسیر fold_0/log_fold0.json است
        $logPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/fold_' . $fold . '/log_fold' . $fold . '.json';
        
        // اگر پیدا نشد، سعی کن با نام دیگر
        if (!file_exists($logPath)) {
            $altPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/fold_' . $fold . '/training_log.json';
            if (file_exists($altPath)) {
                $logPath = $altPath;
            }
        }
    }
    
    if (!file_exists($logPath)) {
        echo json_encode(['success' => false, 'error' => 'فایل log پیدا نشد: ' . $logPath]);
        return;
    }
    
    // بارگذاری فایل JSON
    $data = json_decode(file_get_contents($logPath), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'خطا در خواندن فایل JSON']);
        return;
    }
    
    // استخراج داده‌ها
    $key = $chartType === 'accuracy' ? ($dataType === 'train' ? 'train_acc' : 'val_acc') : ($dataType === 'train' ? 'train_loss' : 'val_loss');
    
    if (!isset($data[$key])) {
        echo json_encode(['success' => false, 'error' => 'کلید ' . $key . ' در فایل پیدا نشد']);
        return;
    }
    
    $values = $data[$key];
    
    // تبدیل به float
    $values = array_map('floatval', $values);
    
    echo json_encode([
        'success' => true,
        'values' => $values,
        'epochs' => $data['epoch'] ?? range(1, count($values))
    ]);
}

function getSubjectData($resultsPath) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $model = $input['model'] ?? '';
    $validation = $input['validation'] ?? '';
    $run = $input['run'] ?? '';
    $fold = $input['fold'] ?? '';
    $dataType = $input['dataType'] ?? 'train'; // train or test
    $chartType = $input['chartType'] ?? 'accuracy'; // accuracy or loss
    
    if (!$model || !$validation || !$run || $fold === '' || $validation !== 'subject_dependent') {
        echo json_encode(['success' => false, 'error' => 'پارامترهای لازم ارسال نشد یا validation type باید subject_dependent باشد']);
        return;
    }
    
    $runPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run;
    
    // پیدا کردن همه subject ها
    $subjects = [];
    if (is_dir($runPath)) {
        $items = scandir($runPath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($runPath . '/' . $item) && strpos($item, 'subject_') === 0) {
                $subjects[] = $item;
            }
        }
    }
    
    // مرتب‌سازی بر اساس شماره subject
    usort($subjects, function($a, $b) {
        $numA = intval(str_replace('subject_', '', $a));
        $numB = intval(str_replace('subject_', '', $b));
        return $numA - $numB;
    });
    
    if (empty($subjects)) {
        echo json_encode(['success' => false, 'error' => 'هیچ subject پیدا نشد']);
        return;
    }
    
    $subjectValues = [];
    $subjectLabels = [];
    
    foreach ($subjects as $subject) {
        $subjectNum = intval(str_replace('subject_', '', $subject));
        $subjectLabels[] = $subjectNum;
        
        // اگر fold برابر "average" باشد، میانگین 5 fold را محاسبه کن
        if ($fold === 'average') {
            $allValues = [];
            for ($i = 0; $i <= 4; $i++) {
                $logPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/' . $subject . '/log_fold' . $i . '.json';
                if (file_exists($logPath)) {
                    $data = json_decode(file_get_contents($logPath), true);
                    if ($data) {
                        $key = $chartType === 'accuracy' ? ($dataType === 'train' ? 'train_acc' : 'val_acc') : ($dataType === 'train' ? 'train_loss' : 'val_loss');
                        if (isset($data[$key])) {
                            $values = array_map('floatval', $data[$key]);
                            $allValues[] = $values;
                        }
                    }
                }
            }
            
            if (!empty($allValues)) {
                // محاسبه میانگین برای هر epoch
                $maxLength = max(array_map('count', $allValues));
                $averageValues = [];
                for ($epoch = 0; $epoch < $maxLength; $epoch++) {
                    $sum = 0;
                    $count = 0;
                    foreach ($allValues as $values) {
                        if (isset($values[$epoch])) {
                            $sum += $values[$epoch];
                            $count++;
                        }
                    }
                    $averageValues[] = $count > 0 ? $sum / $count : 0;
                }
                
                // محاسبه میانگین 5 اپک آخر
                $last5Epochs = array_slice($averageValues, -5);
                $subjectValues[] = count($last5Epochs) > 0 ? array_sum($last5Epochs) / count($last5Epochs) : 0;
            } else {
                $subjectValues[] = 0;
            }
        } else {
            // fold خاص
            $logPath = $resultsPath . '/' . $model . '/' . $validation . '/' . $run . '/' . $subject . '/log_fold' . $fold . '.json';
            if (file_exists($logPath)) {
                $data = json_decode(file_get_contents($logPath), true);
                if ($data) {
                    $key = $chartType === 'accuracy' ? ($dataType === 'train' ? 'train_acc' : 'val_acc') : ($dataType === 'train' ? 'train_loss' : 'val_loss');
                    if (isset($data[$key])) {
                        $values = array_map('floatval', $data[$key]);
                        // محاسبه میانگین 5 اپک آخر
                        $last5Epochs = array_slice($values, -5);
                        $subjectValues[] = count($last5Epochs) > 0 ? array_sum($last5Epochs) / count($last5Epochs) : 0;
                    } else {
                        $subjectValues[] = 0;
                    }
                } else {
                    $subjectValues[] = 0;
                }
            } else {
                $subjectValues[] = 0;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'values' => $subjectValues,
        'labels' => $subjectLabels
    ]);
}
?>

