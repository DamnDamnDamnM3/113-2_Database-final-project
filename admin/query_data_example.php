<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料排序範例</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #666;
            text-decoration: none;
        }

        .back-link:hover {
            color: #333;
        }

        .count {
            margin-top: 10px;
            color: #666;
        }

        .sort-info {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <a href="home_page.php" class="back-link">← 返回主選單</a>
    <h1>資料排序範例</h1>

    <div class="form-group">
        <label for="tableName">資料表名稱：</label>
        <input type="text" id="tableName" placeholder="請輸入資料表名稱">
    </div>

    <button onclick="fetchData()">獲取資料</button>

    <div id="sortControls" style="display: none; margin-top: 20px;">
        <div class="form-group">
            <label for="sortColumn">排序欄位：</label>
            <select id="sortColumn"></select>
        </div>

        <div class="form-group">
            <label for="sortOrder">排序方向：</label>
            <select id="sortOrder">
                <option value="ASC">升序 (A-Z)</option>
                <option value="DESC">降序 (Z-A)</option>
            </select>
        </div>

        <button onclick="sortData()">排序</button>
    </div>

    <div id="result" class="result"></div>

    <script>
        let currentData = null;

        async function fetchData() {
            const tableName = document.getElementById('tableName').value;
            const resultDiv = document.getElementById('result');
            const sortControls = document.getElementById('sortControls');

            if (!tableName) {
                resultDiv.innerHTML = '<p class="error">請填寫資料表名稱</p>';
                return;
            }

            try {
                // 先使用 get_all/main.php 獲取所有資料
                const response = await fetch(`get_all/main.php?table=${encodeURIComponent(tableName)}`);
                const data = await response.json();

                if (data.status === 'success') {
                    if (data.data.length === 0) {
                        resultDiv.innerHTML = '<p>資料表中沒有資料</p>';
                        sortControls.style.display = 'none';
                        return;
                    }

                    // 儲存資料供排序使用
                    currentData = data.data;

                    // 更新排序欄位選項
                    const sortColumn = document.getElementById('sortColumn');
                    sortColumn.innerHTML = '';
                    Object.keys(data.data[0]).forEach(key => {
                        const option = document.createElement('option');
                        option.value = key;
                        option.textContent = key;
                        sortColumn.appendChild(option);
                    });

                    // 顯示排序控制項
                    sortControls.style.display = 'block';

                    // 顯示原始資料
                    displayData(data.data);
                } else {
                    resultDiv.innerHTML = `<p class="error">錯誤：${data.message}</p>`;
                    sortControls.style.display = 'none';
                }
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">發生錯誤：${error.message}</p>`;
                sortControls.style.display = 'none';
            }
        }

        async function sortData() {
            if (!currentData) {
                return;
            }

            const sortColumn = document.getElementById('sortColumn').value;
            const sortOrder = document.getElementById('sortOrder').value;
            const resultDiv = document.getElementById('result');

            try {
                // 使用 query_data/main.php 進行排序
                const response = await fetch('query_data/main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        data: currentData,
                        sort_column: sortColumn,
                        sort_order: sortOrder
                    })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    displayData(data.data, data.sort_info);
                } else {
                    resultDiv.innerHTML = `<p class="error">錯誤：${data.message}</p>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">發生錯誤：${error.message}</p>`;
            }
        }

        function displayData(data, sortInfo = null) {
            const resultDiv = document.getElementById('result');

            let tableHtml = '<h3>查詢結果：</h3>';
            tableHtml += '<table>';

            // 建立表頭
            tableHtml += '<tr>';
            Object.keys(data[0]).forEach(key => {
                tableHtml += `<th>${key}</th>`;
            });
            tableHtml += '</tr>';

            // 加入資料行
            data.forEach(row => {
                tableHtml += '<tr>';
                Object.values(row).forEach(value => {
                    tableHtml += `<td>${value}</td>`;
                });
                tableHtml += '</tr>';
            });

            tableHtml += '</table>';
            tableHtml += `<p class="count">共找到 ${data.length} 筆資料</p>`;

            if (sortInfo) {
                tableHtml += `
                    <div class="sort-info">
                        <p>排序資訊：</p>
                        <ul>
                            <li>排序欄位：${sortInfo.column}</li>
                            <li>排序方向：${sortInfo.order}</li>
                            <li>資料總數：${sortInfo.total_rows}</li>
                        </ul>
                    </div>
                `;
            }

            resultDiv.innerHTML = tableHtml;
        }
    </script>
</body>

</html>