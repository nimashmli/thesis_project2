<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EEG Emotion Recognition - Results Viewer</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        @font-face {
            font-family: 'B Nazanin';
            src: url('B_NAZANIN/B-NAZANIN.TTF') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'B Nazanin', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            font-family: 'B Nazanin', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.15) 0%, rgba(247, 147, 30, 0.15) 50%, rgba(255, 69, 0, 0.15) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(255, 69, 0, 0.3);
            padding: 30px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.2) 0%, rgba(255, 69, 0, 0.2) 100%);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(255, 69, 0, 0.2);
            border: 2px solid rgba(255, 107, 53, 0.3);
        }

        .controls {
            background: linear-gradient(135deg, rgba(255, 245, 240, 0.8) 0%, rgba(255, 232, 224, 0.8) 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px solid rgba(255, 107, 53, 0.3);
        }

        .add-chart-btn, .add-canvas-btn {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.8) 0%, rgba(255, 69, 0, 0.8) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 69, 0, 0.3);
            font-weight: bold;
        }

        .add-chart-btn:hover, .add-canvas-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 69, 0, 0.6);
        }

        .buttons-row {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .canvases-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(700px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .canvas-box {
            background: white;
            border: 3px solid rgba(255, 107, 53, 0.3);
            border-radius: 15px;
            padding: 20px;
            position: relative;
            box-shadow: 0 4px 15px rgba(255, 69, 0, 0.15);
            min-height: 550px;
        }

        .canvas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ffe8e0;
        }

        .canvas-title-input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-right: 10px;
        }

        .canvas-title-input:focus {
            outline: none;
            border-color: #ff4500;
            box-shadow: 0 0 10px rgba(255, 69, 0, 0.3);
        }

        .download-btn {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.8) 0%, rgba(255, 69, 0, 0.8) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .download-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(255, 69, 0, 0.4);
        }

        .remove-canvas-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
            transition: all 0.3s;
        }

        .remove-canvas-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .chart-wrapper {
            position: relative;
            display: flex;
            gap: 10px;
            height: 450px;
            margin-bottom: 20px;
            background: #fafafa;
            border-radius: 10px;
            padding: 10px;
        }

        .chart-canvas-container {
            position: relative;
            flex: 1;
            height: 100%;
        }

        .chart-canvas-container canvas {
            width: 100%;
            height: 100%;
        }

        .legend-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 6px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            border: 2px solid rgba(255, 107, 53, 0.3);
            min-width: 120px;
            max-width: 140px;
            max-height: 250px;
            overflow-y: auto;
            align-self: flex-start;
            margin-top: 10px;
        }

        .legend-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 11px;
            border-bottom: 1px solid rgba(255, 107, 53, 0.3);
            padding-bottom: 3px;
            text-align: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 3px 0;
            padding: 3px;
            border-radius: 3px;
            transition: background 0.2s;
        }

        .legend-item:hover {
            background: #ffe8e0;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            border: 1px solid #ddd;
            flex-shrink: 0;
        }

        .legend-name {
            flex: 1;
            font-size: 11px;
            color: #333;
            font-weight: 500;
        }

        .chart-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding: 15px;
            background: #fff5f0;
            border-radius: 10px;
            max-height: 200px;
            overflow-y: auto;
        }

        .chart-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 2px solid #ff6b35;
            box-shadow: 0 2px 5px rgba(255, 69, 0, 0.2);
        }

        .color-picker {
            width: 40px;
            height: 40px;
            border: 2px solid #ff6b35;
            border-radius: 5px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .name-input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            width: 200px;
        }

        .name-input:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.2s;
        }

        .remove-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 20px 60px rgba(255, 69, 0, 0.4);
            animation: slideDown 0.3s;
            border: 3px solid #ff6b35;
            position: relative;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            color: #ff6b35;
            float: left;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            position: absolute;
            top: 15px;
            left: 20px;
        }

        .close:hover {
            color: #ff4500;
            transform: scale(1.2);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }

        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            color: #333;
        }

        .form-group select:focus,
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: rgba(255, 107, 53, 0.6);
            box-shadow: 0 0 10px rgba(255, 69, 0, 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.8) 0%, rgba(255, 69, 0, 0.8) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 69, 0, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 69, 0, 0.6);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 18px;
        }

        .canvas-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .type-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid rgba(255, 107, 53, 0.3);
            background: white;
            color: #333;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 15px;
        }

        .type-btn.active {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.8) 0%, rgba(255, 69, 0, 0.8) 100%);
            color: white;
        }

        .type-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 69, 0, 0.3);
        }

        .color-input-wrapper {
            position: relative;
        }

        .color-input-wrapper input[type="color"] {
            width: 100%;
            height: 60px;
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 EEG Emotion Recognition - Results Viewer</h1>
        
        <div class="controls">
            <div class="buttons-row">
                <button class="add-canvas-btn" onclick="addNewCanvas()">
                    <span style="font-size: 24px;">+</span>
                    <span>افزودن Canvas جدید</span>
                </button>
                <button class="add-chart-btn" onclick="openModal()">
                    <span style="font-size: 24px;">+</span>
                    <span>افزودن نمودار</span>
                </button>
            </div>
        </div>

        <div class="canvases-container" id="canvasesContainer">
            <!-- Canvas‌ها به صورت داینامیک اضافه می‌شوند -->
        </div>
    </div>

    <!-- Modal برای افزودن نمودار -->
    <div id="chartModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="margin-bottom: 25px; text-align: center; color: #333; margin-top: 20px;">افزودن نمودار جدید</h2>
            <form id="chartForm" onsubmit="addChart(event)">
                <div class="form-group">
                    <label>انتخاب Canvas:</label>
                    <select id="canvasSelect" required>
                        <option value="">ابتدا یک Canvas اضافه کنید</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>نوع نمودار:</label>
                    <div class="canvas-type-selector">
                        <button type="button" class="type-btn active" data-type="accuracy" onclick="selectChartType('accuracy')">Accuracy</button>
                        <button type="button" class="type-btn" data-type="loss" onclick="selectChartType('loss')">Loss</button>
                    </div>
                    <input type="hidden" id="chartType" value="accuracy">
                </div>
                <div class="form-group">
                    <label>نوع داده:</label>
                    <div class="canvas-type-selector">
                        <button type="button" class="type-btn active" data-type="train" onclick="selectDataType('train')">Train</button>
                        <button type="button" class="type-btn" data-type="test" onclick="selectDataType('test')">Test</button>
                    </div>
                    <input type="hidden" id="dataType" value="train">
                </div>
                <div class="form-group">
                    <label>معماری (Model):</label>
                    <select id="modelName" required onchange="loadRuns()">
                        <option value="">انتخاب کنید...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>نوع Validation:</label>
                    <select id="validationType" required onchange="loadRuns()">
                        <option value="subject_dependent">Subject Dependent</option>
                        <option value="subject_independent">Subject Independent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Run:</label>
                    <select id="runName" required onchange="loadSubjects()">
                        <option value="">ابتدا Model و Validation را انتخاب کنید</option>
                    </select>
                </div>
                <div class="form-group" id="subjectGroup" style="display: none;">
                    <label>Subject:</label>
                    <select id="subjectName">
                        <option value="">انتخاب کنید...</option>
                    </select>
                </div>
                <div class="form-group" id="chartModeGroup">
                    <label>نوع نمودار:</label>
                    <div class="canvas-type-selector">
                        <button type="button" class="type-btn active" data-mode="epoch" onclick="selectChartMode('epoch')">Epoch-based</button>
                        <button type="button" class="type-btn" data-mode="subject" onclick="selectChartMode('subject')">Subject-based</button>
                    </div>
                    <input type="hidden" id="chartMode" value="epoch">
                </div>
                <div class="form-group" id="foldGroup">
                    <label>Fold:</label>
                    <select id="foldNumber" required>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="average">Average (میانگین 0-4)</option>
                    </select>
                </div>
                <div class="form-group" id="lineStyleGroup">
                    <label>نوع خط:</label>
                    <select id="lineStyle" required>
                        <option value="solid">خط معمولی</option>
                        <option value="dashed">خط نقطه چین</option>
                        <option value="dotted">خط نقطه دار</option>
                        <option value="dashdot">خط نقطه چین-نقطه</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>نام منحنی:</label>
                    <input type="text" id="curveName" placeholder="مثلاً: Test Accuracy - Subject 0" required>
                </div>
                <div class="form-group">
                    <label>رنگ:</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="curveColor" value="#ff6b35" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">افزودن به نمودار</button>
            </form>
        </div>
    </div>

    <script>
        // متغیرهای全局
        let canvases = [];
        let canvasCounter = 0;

        // انتخاب نوع نمودار
        function selectChartType(type) {
            document.getElementById('chartType').value = type;
            document.querySelectorAll('.type-btn[data-type]').forEach(btn => {
                if ((btn.dataset.type === type) && (btn.textContent === 'Accuracy' || btn.textContent === 'Loss')) {
                    btn.classList.add('active');
                } else if (btn.textContent === 'Accuracy' || btn.textContent === 'Loss') {
                    btn.classList.remove('active');
                }
            });
        }

        // انتخاب نوع داده
        function selectDataType(type) {
            document.getElementById('dataType').value = type;
            document.querySelectorAll('.type-btn[data-type]').forEach(btn => {
                if (btn.dataset.type === type && (btn.textContent === 'Train' || btn.textContent === 'Test')) {
                    btn.classList.add('active');
                } else if (btn.textContent === 'Train' || btn.textContent === 'Test') {
                    btn.classList.remove('active');
                }
            });
        }

        // انتخاب نوع نمودار (Epoch-based یا Subject-based)
        function selectChartMode(mode) {
            document.getElementById('chartMode').value = mode;
            document.querySelectorAll('.type-btn[data-mode]').forEach(btn => {
                if (btn.dataset.mode === mode) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // نمایش/مخفی کردن فیلدهای مربوطه
            const subjectGroup = document.getElementById('subjectGroup');
            const foldGroup = document.getElementById('foldGroup');
            const validationType = document.getElementById('validationType').value;
            
            if (mode === 'subject') {
                // برای subject-based، باید validation type subject_dependent باشد
                if (validationType === 'subject_dependent') {
                    subjectGroup.style.display = 'none'; // نیازی به انتخاب subject نیست
                    const subjectSelect = document.getElementById('subjectName');
                    subjectSelect.required = false;
                    subjectSelect.removeAttribute('required');
                    foldGroup.style.display = 'block';
                } else {
                    alert('نمودار Subject-based فقط برای Subject Dependent در دسترس است!');
                    selectChartMode('epoch');
                    return;
                }
            } else {
                // برای epoch-based
                if (validationType === 'subject_dependent') {
                    subjectGroup.style.display = 'block';
                    const subjectSelect = document.getElementById('subjectName');
                    subjectSelect.required = true;
                    subjectSelect.setAttribute('required', 'required');
                } else {
                    subjectGroup.style.display = 'none';
                    const subjectSelect = document.getElementById('subjectName');
                    subjectSelect.required = false;
                    subjectSelect.removeAttribute('required');
                }
                foldGroup.style.display = 'block';
            }
        }

        // افزودن Canvas جدید
        function addNewCanvas() {
            const canvasId = `canvas_${canvasCounter++}`;
            const canvas = {
                id: canvasId,
                chart: null,
                data: {
                    datasets: [],
                    labels: []
                },
                items: []
            };
            canvases.push(canvas);
            
            const container = document.getElementById('canvasesContainer');
            const canvasBox = document.createElement('div');
            canvasBox.className = 'canvas-box';
            canvasBox.id = canvasId + '_box';
            canvasBox.innerHTML = `
                <div class="canvas-header">
                    <input type="text" class="canvas-title-input" value="Canvas ${canvasCounter}" 
                           onchange="updateCanvasTitle('${canvasId}', this.value)">
                    <div>
                        <button class="download-btn" onclick="downloadCanvas('${canvasId}')">💾 دانلود</button>
                        <button class="remove-canvas-btn" onclick="removeCanvas('${canvasId}')">🗑️ حذف</button>
                    </div>
                </div>
                <div style="margin-bottom: 10px; padding: 0 5px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <div>
                        <label style="font-size: 14px; color: #333; margin-left: 10px;">عنوان نمودار:</label>
                        <input type="text" id="${canvasId}_chartTitle" value="Chart" 
                               style="padding: 8px 12px; border: 2px solid rgba(255, 107, 53, 0.3); border-radius: 5px; font-size: 14px; width: 200px; color: #333; margin-right: 10px;"
                               onchange="updateChartTitle('${canvasId}', this.value)">
                    </div>
                    <div>
                        <label style="font-size: 14px; color: #333; margin-left: 10px;">برچسب محور Y:</label>
                        <input type="text" id="${canvasId}_yAxisLabel" value="Value" 
                               style="padding: 8px 12px; border: 2px solid rgba(255, 107, 53, 0.3); border-radius: 5px; font-size: 14px; width: 200px; color: #333; margin-right: 10px;"
                               onchange="updateYAxisLabel('${canvasId}', this.value)">
                    </div>
                    <div>
                        <label style="font-size: 14px; color: #333; margin-left: 10px;">عنوان Legend:</label>
                        <input type="text" id="${canvasId}_legendTitle" value="Legend" 
                               style="padding: 8px 12px; border: 2px solid rgba(255, 107, 53, 0.3); border-radius: 5px; font-size: 14px; width: 200px; color: #333; margin-right: 10px;"
                               onchange="updateLegendTitle('${canvasId}', this.value)">
                    </div>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-canvas-container">
                        <canvas id="${canvasId}_chart"></canvas>
                    </div>
                    <div class="legend-container" id="${canvasId}_legend">
                        <div class="legend-title" id="${canvasId}_legendTitleText">Legend</div>
                    </div>
                </div>
                <div class="chart-controls" id="${canvasId}_controls"></div>
            `;
            container.appendChild(canvasBox);
            
            // ایجاد نمودار خالی
            initCanvasChart(canvasId);
            updateCanvasSelect();
        }

        // مقداردهی اولیه نمودار برای یک Canvas
        function initCanvasChart(canvasId) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas) return;
            
            const ctx = document.getElementById(canvasId + '_chart').getContext('2d');
            const chartTitleInput = document.getElementById(canvasId + '_chartTitle');
            const yAxisLabelInput = document.getElementById(canvasId + '_yAxisLabel');
            const initialTitle = chartTitleInput ? chartTitleInput.value : 'Chart';
            const initialYLabel = yAxisLabelInput ? yAxisLabelInput.value : 'Value';
            canvas.chart = new Chart(ctx, {
                type: 'line',
                data: canvas.data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    backgroundColor: 'white',
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: initialTitle,
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            color: '#333'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: initialYLabel,
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                color: '#333'
                            },
                            ticks: {
                                color: '#333'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Epoch',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                color: '#333'
                            },
                            ticks: {
                                color: '#333'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    },
                }
            });
        }

        // به‌روزرسانی لیست Canvas‌ها در Modal
        function updateCanvasSelect() {
            const select = document.getElementById('canvasSelect');
            select.innerHTML = '';
            canvases.forEach((canvas, index) => {
                const option = document.createElement('option');
                option.value = canvas.id;
                option.textContent = `Canvas ${index + 1}`;
                select.appendChild(option);
            });
        }

        // بارگذاری مدل‌ها
        async function loadModels() {
            try {
                const response = await fetch('api.php?action=getModels');
                const data = await response.json();
                const select = document.getElementById('modelName');
                select.innerHTML = '<option value="">انتخاب کنید...</option>';
                if (data.models) {
                    data.models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading models:', error);
                alert('خطا در بارگذاری مدل‌ها. مطمئن شوید که api.php در دسترس است.');
            }
        }

        // بارگذاری run‌ها
        async function loadRuns() {
            const modelName = document.getElementById('modelName').value;
            const validationType = document.getElementById('validationType').value;
            
            if (!modelName) return;
            
            try {
                const response = await fetch(`api.php?action=getRuns&model=${encodeURIComponent(modelName)}&validation=${encodeURIComponent(validationType)}`);
                const data = await response.json();
                const select = document.getElementById('runName');
                select.innerHTML = '<option value="">انتخاب کنید...</option>';
                if (data.runs) {
                    data.runs.forEach(run => {
                        const option = document.createElement('option');
                        option.value = run;
                        option.textContent = run;
                        select.appendChild(option);
                    });
                }
                
                if (validationType === 'subject_dependent') {
                    const chartMode = document.getElementById('chartMode').value;
                    const subjectSelect = document.getElementById('subjectName');
                    if (chartMode === 'subject') {
                        document.getElementById('subjectGroup').style.display = 'none';
                        subjectSelect.required = false;
                        subjectSelect.removeAttribute('required');
                    } else {
                        document.getElementById('subjectGroup').style.display = 'block';
                        subjectSelect.required = true;
                        subjectSelect.setAttribute('required', 'required');
                    }
                } else {
                    document.getElementById('subjectGroup').style.display = 'none';
                    const subjectSelect = document.getElementById('subjectName');
                    subjectSelect.required = false;
                    subjectSelect.removeAttribute('required');
                    // اگر subject_independent است، فقط epoch-based مجاز است
                    if (document.getElementById('chartMode').value === 'subject') {
                        selectChartMode('epoch');
                    }
                }
                
                // پاک کردن subject select
                document.getElementById('subjectName').innerHTML = '<option value="">انتخاب کنید...</option>';
            } catch (error) {
                console.error('Error loading runs:', error);
            }
        }

        // بارگذاری subject‌ها
        async function loadSubjects() {
            const modelName = document.getElementById('modelName').value;
            const validationType = document.getElementById('validationType').value;
            const runName = document.getElementById('runName').value;
            
            if (!modelName || !runName || validationType !== 'subject_dependent') return;
            
            try {
                const response = await fetch(`api.php?action=getSubjects&model=${encodeURIComponent(modelName)}&validation=${encodeURIComponent(validationType)}&run=${encodeURIComponent(runName)}`);
                const data = await response.json();
                const select = document.getElementById('subjectName');
                select.innerHTML = '<option value="">انتخاب کنید...</option>';
                if (data.subjects) {
                    data.subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading subjects:', error);
            }
        }

        // باز کردن modal
        function openModal() {
            if (canvases.length === 0) {
                alert('لطفاً ابتدا یک Canvas اضافه کنید!');
                return;
            }
            document.getElementById('chartModal').style.display = 'block';
            loadModels();
            updateCanvasSelect();
        }

        // بستن modal
        function closeModal() {
            document.getElementById('chartModal').style.display = 'none';
            document.getElementById('chartForm').reset();
            selectChartType('accuracy');
            selectDataType('train');
            selectChartMode('epoch');
        }

        // بستن modal با کلیک خارج از آن
        window.onclick = function(event) {
            const modal = document.getElementById('chartModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // تبدیل نوع خط به فرمت Chart.js
        function getBorderDash(lineStyle) {
            switch(lineStyle) {
                case 'dashed':
                    return [10, 5];
                case 'dotted':
                    return [2, 2];
                case 'dashdot':
                    return [10, 5, 2, 5];
                default:
                    return [];
            }
        }

        // افزودن نمودار
        async function addChart(event) {
            event.preventDefault();
            
            // حذف required از فیلدهای hidden و مخفی قبل از validation
            const form = document.getElementById('chartForm');
            const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
            hiddenInputs.forEach(input => {
                input.removeAttribute('required');
            });
            
            const subjectGroup = document.getElementById('subjectGroup');
            if (subjectGroup && subjectGroup.style.display === 'none') {
                const subjectSelect = document.getElementById('subjectName');
                if (subjectSelect) {
                    subjectSelect.removeAttribute('required');
                }
            }
            
            const canvasId = document.getElementById('canvasSelect').value;
            const chartType = document.getElementById('chartType').value;
            const dataType = document.getElementById('dataType').value;
            const modelName = document.getElementById('modelName').value;
            const validationType = document.getElementById('validationType').value;
            const runName = document.getElementById('runName').value;
            const subjectName = document.getElementById('subjectName').value;
            const foldNumber = document.getElementById('foldNumber').value;
            const curveName = document.getElementById('curveName').value;
            const curveColor = document.getElementById('curveColor').value;
            const chartMode = document.getElementById('chartMode').value;
            const lineStyle = document.getElementById('lineStyle').value;
            
            if (!canvasId) {
                alert('لطفاً یک Canvas انتخاب کنید!');
                return;
            }
            
            // برای subject-based، باید validation type subject_dependent باشد
            if (chartMode === 'subject' && validationType !== 'subject_dependent') {
                alert('نمودار Subject-based فقط برای Subject Dependent در دسترس است!');
                return;
            }
            
            try {
                const action = chartMode === 'subject' ? 'getSubjectData' : 'getData';
                const response = await fetch(`api.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        model: modelName,
                        validation: validationType,
                        run: runName,
                        subject: subjectName,
                        fold: foldNumber,
                        dataType: dataType,
                        chartType: chartType
                    })
                });
                
                const data = await response.json();
                
                console.log('Response data:', data); // Debug log
                
                if (data.success) {
                    const canvas = canvases.find(c => c.id === canvasId);
                    if (!canvas) {
                        alert('Canvas پیدا نشد!');
                        return;
                    }
                    
                    if (!data.values || data.values.length === 0) {
                        alert('داده‌ای برای نمایش وجود ندارد!');
                        return;
                    }
                    
                    const itemInfo = {
                        name: curveName,
                        color: curveColor,
                        model: modelName,
                        validation: validationType,
                        run: runName,
                        subject: subjectName,
                        fold: foldNumber,
                        dataType: dataType,
                        chartType: chartType,
                        chartMode: chartMode,
                        lineStyle: lineStyle
                    };
                    
                    // تنظیمات dataset بر اساس نوع نمودار
                    const datasetConfig = {
                        label: curveName,
                        data: data.values,
                        borderColor: curveColor,
                        backgroundColor: curveColor + '40',
                        tension: 0.4,
                        fill: false,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                        borderDash: getBorderDash(lineStyle)
                    };
                    
                    try {
                        // برای subject-based، labels را از data بگیر
                        if (chartMode === 'subject') {
                            if (!data.labels) {
                                // اگر labels وجود ندارد، از شماره subject ها استفاده کن
                                data.labels = data.values.map((_, index) => index);
                            }
                            const index = addDatasetToCanvasWithLabels(canvasId, datasetConfig, itemInfo, data.labels);
                            if (index >= 0) {
                                addControlItem(canvasId, index, curveName, curveColor);
                                updateLegend(canvasId);
                                closeModal();
                            } else {
                                alert('خطا در افزودن dataset به Canvas!');
                            }
                        } else {
                            const index = addDatasetToCanvas(canvasId, datasetConfig, itemInfo);
                            if (index >= 0) {
                                addControlItem(canvasId, index, curveName, curveColor);
                                updateLegend(canvasId);
                                closeModal();
                            } else {
                                alert('خطا در افزودن dataset به Canvas!');
                            }
                        }
                    } catch (error) {
                        console.error('Error adding dataset:', error);
                        alert('خطا در افزودن dataset: ' + error.message);
                    }
                } else {
                    alert('خطا: ' + (data.error || 'خطای نامشخص'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('خطا در بارگذاری داده‌ها: ' + error.message);
            }
        }

        // افزودن dataset به Canvas
        function addDatasetToCanvas(canvasId, dataset, itemInfo) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas) return -1;
            
            // تنظیم labels
            const existingLengths = canvas.data.datasets.map(d => d.data.length);
            const maxLength = existingLengths.length > 0 
                ? Math.max(...existingLengths, dataset.data.length) 
                : dataset.data.length;
            canvas.data.labels = Array.from({length: maxLength}, (_, i) => i + 1);
            
            const index = canvas.data.datasets.length;
            canvas.data.datasets.push(dataset);
            canvas.items.push(itemInfo);
            
            updateCanvasChart(canvasId);
            return index;
        }

        // افزودن dataset با labels خاص (برای subject-based)
        function addDatasetToCanvasWithLabels(canvasId, dataset, itemInfo, newLabels) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas) return -1;
            
            // اگر labels قبلی وجود دارد و با newLabels متفاوت است، باید تصمیم بگیریم
            // برای subject-based، labels باید subject numbers باشند
            if (canvas.data.labels.length === 0 || canvas.items.some(item => item.chartMode === 'subject')) {
                // اگر اولین subject-based است یا labels خالی است
                canvas.data.labels = newLabels;
            } else {
                // اگر قبلاً epoch-based داشتیم، باید labels را ترکیب کنیم
                // در این حالت، فقط subject-based را اضافه می‌کنیم
                canvas.data.labels = newLabels;
            }
            
            const index = canvas.data.datasets.length;
            canvas.data.datasets.push(dataset);
            canvas.items.push(itemInfo);
            
            updateCanvasChart(canvasId);
            return index;
        }

        // به‌روزرسانی نمودار Canvas
        function updateCanvasChart(canvasId) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas || !canvas.chart) {
                console.error('Canvas or chart not found for:', canvasId);
                return;
            }
            
            canvas.chart.data = canvas.data;
            
            // به‌روزرسانی عنوان محور X بر اساس نوع داده
            const hasSubjectBased = canvas.items.some(item => item.chartMode === 'subject');
            if (hasSubjectBased) {
                canvas.chart.options.scales.x.title.text = 'Subject';
            } else {
                canvas.chart.options.scales.x.title.text = 'Epoch';
            }
            
            try {
                canvas.chart.update();
            } catch (error) {
                console.error('Error updating chart:', error);
            }
        }

        // افزودن کنترل item
        function addControlItem(canvasId, index, name, color) {
            const controlsDiv = document.getElementById(canvasId + '_controls');
            const item = document.createElement('div');
            item.className = 'chart-item';
            item.setAttribute('data-index', index);
            item.innerHTML = `
                <div class="color-picker" style="background: ${color}"></div>
                <input type="text" class="name-input" value="${name}" 
                       onchange="updateDatasetLabel('${canvasId}', ${index}, this.value)">
                <input type="color" value="${color}" 
                       onchange="updateDatasetColor('${canvasId}', ${index}, this.value)">
                <button class="remove-btn" onclick="removeDataset('${canvasId}', ${index}, this)">حذف</button>
            `;
            controlsDiv.appendChild(item);
        }

        // به‌روزرسانی label
        function updateDatasetLabel(canvasId, index, newLabel) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (canvas && canvas.data.datasets[index] && canvas.items[index]) {
                canvas.data.datasets[index].label = newLabel;
                canvas.items[index].name = newLabel;
                updateCanvasChart(canvasId);
                updateLegend(canvasId);
            }
        }

        // به‌روزرسانی رنگ
        function updateDatasetColor(canvasId, index, newColor) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (canvas && canvas.data.datasets[index] && canvas.items[index]) {
                canvas.data.datasets[index].borderColor = newColor;
                canvas.data.datasets[index].backgroundColor = newColor + '40';
                canvas.items[index].color = newColor;
                updateCanvasChart(canvasId);
                updateLegend(canvasId);
                
                // به‌روزرسانی color picker در controls
                const controls = document.getElementById(canvasId + '_controls');
                const items = controls.getElementsByClassName('chart-item');
                for (let i = 0; i < items.length; i++) {
                    if (items[i].getAttribute('data-index') == index) {
                        const colorPicker = items[i].querySelector('.color-picker');
                        if (colorPicker) {
                            colorPicker.style.background = newColor;
                        }
                        break;
                    }
                }
            }
        }

        // حذف dataset
        function removeDataset(canvasId, index, button) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas) return;
            
            canvas.data.datasets.splice(index, 1);
            canvas.items.splice(index, 1);
            
            button.parentElement.remove();
            updateCanvasChart(canvasId);
            updateLegend(canvasId);
            
            // به‌روزرسانی index‌های باقی‌مانده
            const controlsDiv = document.getElementById(canvasId + '_controls');
            const items = Array.from(controlsDiv.getElementsByClassName('chart-item'));
            items.forEach((item) => {
                const currentIndex = parseInt(item.getAttribute('data-index'));
                if (currentIndex > index) {
                    const newIndex = currentIndex - 1;
                    item.setAttribute('data-index', newIndex);
                    const nameInput = item.querySelector('.name-input');
                    const colorInput = item.querySelector('input[type="color"]');
                    const removeBtn = item.querySelector('.remove-btn');
                    
                    nameInput.setAttribute('onchange', `updateDatasetLabel('${canvasId}', ${newIndex}, this.value)`);
                    colorInput.setAttribute('onchange', `updateDatasetColor('${canvasId}', ${newIndex}, this.value)`);
                    removeBtn.setAttribute('onclick', `removeDataset('${canvasId}', ${newIndex}, this)`);
                }
            });
        }

        // به‌روزرسانی Legend
        function updateLegend(canvasId) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas) return;
            
            const legendDiv = document.getElementById(canvasId + '_legend');
            const legendTitleInput = document.getElementById(canvasId + '_legendTitle');
            const legendTitle = legendTitleInput ? legendTitleInput.value : 'Legend';
            legendDiv.innerHTML = `<div class="legend-title">${legendTitle}</div>`;
            
            // Legend همیشه در بالا سمت راست، خارج از نمودار قرار می‌گیرد
            legendDiv.className = 'legend-container';
            
            canvas.items.forEach((item) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item';
                legendItem.innerHTML = `
                    <div class="legend-color" style="background: ${item.color}"></div>
                    <div class="legend-name">${item.name}</div>
                `;
                legendDiv.appendChild(legendItem);
            });
        }

        // به‌روزرسانی عنوان Canvas
        function updateCanvasTitle(canvasId, title) {
            // این تابع برای عنوان Canvas استفاده می‌شود (نه عنوان نمودار)
        }

        // به‌روزرسانی عنوان نمودار
        function updateChartTitle(canvasId, title) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (canvas && canvas.chart) {
                canvas.chart.options.plugins.title.text = title;
                canvas.chart.update();
            }
        }

        // به‌روزرسانی برچسب محور Y
        function updateYAxisLabel(canvasId, label) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (canvas && canvas.chart) {
                canvas.chart.options.scales.y.title.text = label;
                canvas.chart.update();
            }
        }

        // به‌روزرسانی عنوان Legend
        function updateLegendTitle(canvasId, title) {
            // فقط عنوان را به‌روزرسانی می‌کنیم، items را حفظ می‌کنیم
            const legendDiv = document.getElementById(canvasId + '_legend');
            if (legendDiv) {
                const titleDiv = legendDiv.querySelector('.legend-title');
                if (titleDiv) {
                    titleDiv.textContent = title;
                }
            }
        }

        // دانلود Canvas با کیفیت بالا
        function downloadCanvas(canvasId) {
            const canvas = canvases.find(c => c.id === canvasId);
            if (!canvas || !canvas.chart) {
                alert('نموداری برای دانلود وجود ندارد!');
                return;
            }
            
            const chart = canvas.chart;
            const originalCanvas = chart.canvas;
            
            // ذخیره تنظیمات فعلی
            const originalOptions = JSON.parse(JSON.stringify(chart.options));
            
            // تنظیم رنگ‌های سفید برای پس‌زمینه
            chart.options.backgroundColor = 'white';
            chart.options.plugins.title.color = '#333';
            chart.options.scales.y.ticks.color = '#333';
            chart.options.scales.x.ticks.color = '#333';
            chart.options.scales.y.title.color = '#333';
            chart.options.scales.x.title.color = '#333';
            
            // به‌روزرسانی نمودار
            chart.update('none');
            
            // ایجاد canvas با کیفیت بالا
            setTimeout(() => {
                try {
                    // استفاده از ابعاد اصلی canvas (نه ابعاد نمایشی که به زوم وابسته است)
                    // DPI مناسب برای کیفیت خوب و حجم مناسب (150 DPI)
                    const dpi = 150;
                    const scale = dpi / 96; // 96 DPI استاندارد صفحه نمایش
                    
                    // استفاده از ابعاد اصلی canvas
                    const sourceWidth = originalCanvas.width;
                    const sourceHeight = originalCanvas.height;
                    
                    // ایجاد canvas موقت با DPI مناسب
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = sourceWidth * scale;
                    tempCanvas.height = sourceHeight * scale;
                    const tempCtx = tempCanvas.getContext('2d');
                    
                    // فعال کردن image smoothing با کیفیت بالا
                    tempCtx.imageSmoothingEnabled = true;
                    tempCtx.imageSmoothingQuality = 'high';
                    
                    // پس‌زمینه سفید
                    tempCtx.fillStyle = 'white';
                    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                    
                    // رسم نمودار در canvas موقت با scale مناسب
                    tempCtx.save();
                    tempCtx.scale(scale, scale);
                    tempCtx.drawImage(originalCanvas, 0, 0);
                    tempCtx.restore();
                    
                    // اضافه کردن Legend به صورت دستی در کنار نمودار (سمت راست)
                    const legendDiv = document.getElementById(canvasId + '_legend');
                    if (legendDiv && legendDiv.style.display !== 'none') {
                        // ابعاد Legend (ثابت - بر اساس استایل CSS)
                        const legendWidth = 140; // max-width از CSS
                        const legendPadding = 6; // padding از CSS
                        const legendItemHeight = 25; // ارتفاع هر item
                        const legendTitleHeight = 30; // ارتفاع عنوان
                        
                        // محاسبه ارتفاع کل Legend
                        const legendItems = legendDiv.querySelectorAll('.legend-item');
                        const legendHeight = legendTitleHeight + (legendItems.length * legendItemHeight) + (legendPadding * 2);
                        
                        // فاصله بین canvas و Legend
                        const gap = 10 * scale;
                        
                        // محاسبه موقعیت Legend در کنار canvas (سمت راست)
                        const legendX = (sourceWidth * scale) + gap;
                        const legendY = 10 * scale; // بالا سمت راست
                        
                        const scaledLegendWidth = legendWidth * scale;
                        const scaledLegendHeight = legendHeight * scale;
                        
                        // ایجاد canvas جدید با عرض بیشتر برای شامل کردن Legend
                        const finalCanvas = document.createElement('canvas');
                        finalCanvas.width = (sourceWidth * scale) + gap + scaledLegendWidth;
                        finalCanvas.height = Math.max(sourceHeight * scale, scaledLegendHeight + (20 * scale));
                        const finalCtx = finalCanvas.getContext('2d');
                        
                        // فعال کردن image smoothing با کیفیت بالا
                        finalCtx.imageSmoothingEnabled = true;
                        finalCtx.imageSmoothingQuality = 'high';
                        
                        // پس‌زمینه سفید
                        finalCtx.fillStyle = 'white';
                        finalCtx.fillRect(0, 0, finalCanvas.width, finalCanvas.height);
                        
                        // کپی نمودار از canvas موقت به canvas نهایی
                        finalCtx.drawImage(tempCanvas, 0, 0);
                        
                        // رسم پس‌زمینه Legend
                        finalCtx.fillStyle = 'rgba(255, 255, 255, 0.98)';
                        finalCtx.fillRect(legendX, legendY, scaledLegendWidth, scaledLegendHeight);
                        
                        // رسم border Legend
                        finalCtx.strokeStyle = 'rgba(255, 107, 53, 0.3)';
                        finalCtx.lineWidth = 2 * scale;
                        finalCtx.strokeRect(legendX, legendY, scaledLegendWidth, scaledLegendHeight);
                        
                        // رسم عنوان Legend
                        const legendTitle = legendDiv.querySelector('.legend-title');
                        if (legendTitle) {
                            finalCtx.fillStyle = '#333';
                            finalCtx.font = `bold ${11 * scale}px B Nazanin, Arial, sans-serif`;
                            finalCtx.textAlign = 'center';
                            finalCtx.textBaseline = 'top';
                            finalCtx.fillText(legendTitle.textContent, legendX + scaledLegendWidth / 2, legendY + (8 * scale));
                            
                            // خط زیر عنوان
                            finalCtx.strokeStyle = 'rgba(255, 107, 53, 0.3)';
                            finalCtx.lineWidth = 1 * scale;
                            finalCtx.beginPath();
                            finalCtx.moveTo(legendX + (10 * scale), legendY + (25 * scale));
                            finalCtx.lineTo(legendX + scaledLegendWidth - (10 * scale), legendY + (25 * scale));
                            finalCtx.stroke();
                        }
                        
                        // رسم items Legend
                        let itemY = legendY + (35 * scale);
                        legendItems.forEach((item) => {
                            const colorBox = item.querySelector('.legend-color');
                            const nameText = item.querySelector('.legend-name');
                            
                            if (colorBox && nameText) {
                                // رسم مربع رنگ
                                const color = window.getComputedStyle(colorBox).backgroundColor;
                                finalCtx.fillStyle = color;
                                finalCtx.fillRect(legendX + (10 * scale), itemY - (10 * scale), 12 * scale, 12 * scale);
                                
                                // رسم border مربع
                                finalCtx.strokeStyle = '#ddd';
                                finalCtx.lineWidth = 1 * scale;
                                finalCtx.strokeRect(legendX + (10 * scale), itemY - (10 * scale), 12 * scale, 12 * scale);
                                
                                // رسم نام
                                finalCtx.fillStyle = '#333';
                                finalCtx.font = `500 ${11 * scale}px B Nazanin, Arial, sans-serif`;
                                finalCtx.textAlign = 'right';
                                finalCtx.textBaseline = 'middle';
                                finalCtx.fillText(nameText.textContent, legendX + scaledLegendWidth - (10 * scale), itemY);
                                
                                itemY += (25 * scale);
                            }
                        });
                        
                        // استفاده از canvas نهایی برای دانلود
                        const url = finalCanvas.toDataURL('image/png', 0.95);
                        const link = document.createElement('a');
                        const title = document.querySelector(`#${canvasId}_box .canvas-title-input`).value || 'chart';
                        link.download = `${title}_${Date.now()}.png`;
                        link.href = url;
                        link.click();
                    } else {
                        // اگر Legend وجود ندارد، از canvas موقت استفاده کن
                        const url = tempCanvas.toDataURL('image/png', 0.95);
                        const link = document.createElement('a');
                        const title = document.querySelector(`#${canvasId}_box .canvas-title-input`).value || 'chart';
                        link.download = `${title}_${Date.now()}.png`;
                        link.href = url;
                        link.click();
                    }
                    
                    // بازگرداندن تنظیمات قبلی
                    chart.options = originalOptions;
                    chart.update('none');
                } catch (error) {
                    console.error('Error creating high quality image:', error);
                    // در صورت خطا، از روش Chart.js استفاده کن
                    const url = chart.toBase64Image('image/png', 1);
                    const link = document.createElement('a');
                    const title = document.querySelector(`#${canvasId}_box .canvas-title-input`).value || 'chart';
                    link.download = `${title}_${Date.now()}.png`;
                    link.href = url;
                    link.click();
                    
                    // بازگرداندن تنظیمات قبلی
                    chart.options = originalOptions;
                    chart.update('none');
                }
            }, 200);
        }

        // حذف Canvas
        function removeCanvas(canvasId) {
            if (!confirm('آیا مطمئن هستید که می‌خواهید این Canvas را حذف کنید؟')) {
                return;
            }
            
            const canvas = canvases.find(c => c.id === canvasId);
            if (canvas && canvas.chart) {
                canvas.chart.destroy();
            }
            
            canvases = canvases.filter(c => c.id !== canvasId);
            const box = document.getElementById(canvasId + '_box');
            if (box) box.remove();
            updateCanvasSelect();
        }

        // مقداردهی اولیه - اضافه کردن یک Canvas خالی
        window.onload = function() {
            addNewCanvas();
        };
    </script>
</body>
</html>
