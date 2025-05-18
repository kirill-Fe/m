<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}
$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];
$name = $_SESSION['name'] ?? 'Без имени';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простой мессенджер</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Привет, <?php echo htmlspecialchars($name); ?>!</h2>
        <p>Ваш ID: <strong><?php echo $userId; ?></strong></p>
        <div class="profile">
            <input type="text" id="nameInput" placeholder="Введите имя" value="<?php echo htmlspecialchars($name); ?>">
            <button onclick="updateName()">Сохранить имя</button>
        </div>
        <div class="search">
            <input type="text" id="searchId" placeholder="Введите ID пользователя">
            <button onclick="stationstart('searchId')">Найти</button>
        </div>
        <div class="chat">
            <h3>Чат</h3>
            <div id="chatBox"></div>
            <input type="text" id="messageInput" placeholder="Введите сообщение">
            <button onclick="sendMessage()">Отправить</button>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-database.js"></script>
    <script>
        // Firebase инициализация
        const firebaseConfig = <?php echo json_encode($firebaseConfig); ?>;
        firebase.initializeApp(firebaseConfig);
        const database = firebase.database();

        // Текущий пользователь
        const userId = '<?php echo $userId; ?>';
        let chatPartnerId = null;

        // Обновление имени
        function updateName() {
            const name = document.getElementById('nameInput').value.trim();
            if (name) {
                database.ref('users/' + userId).set({
                    name: name,
                    email: '<?php echo $email; ?>'
                });
            }
        }

        // Поиск пользователя
        document.getElementById('searchId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchId = this.value.trim();
                if (searchId && searchId !== userId) {
                    database.ref('users/' + searchId).once('value', snapshot => {
                        if (snapshot.exists()) {
                            chatPartnerId = searchId;
                            document.getElementById('chatBox').innerHTML = '';
                            loadMessages();
                        } else {
                            alert('Пользователь не найден!');
                        }
                    });
                }
            }
        });

        // Отправка сообщения
        function sendMessage() {
            const message = document.getElementById('messageInput').value.trim();
            if (message && chatPartnerId) {
                const timestamp = Date.now();
                database.ref('messages/' + userId + '/' + chatPartnerId).push({
                    sender: userId,
                    text: message,
                    timestamp: timestamp
                });
                database.ref('messages/' + chatPartnerId + '/' + userId).push({
                    sender: userId,
                    text: message,
                    timestamp: timestamp
                });
                document.getElementById('messageInput').value = '';
            }
        }

        // Загрузка сообщений
        function loadMessages() {
            if (chatPartnerId) {
                database.ref('messages/' + userId + '/' + chatPartnerId).on('child_added', snapshot => {
                    const msg = snapshot.val();
                    const chatBox = document.getElementById('chatBox');
                    const msgElement = document.createElement('div');
                    msgElement.className = msg.sender === userId ? 'sent' : 'received';
                    msgElement.textContent = msg.text;
                    chatBox.appendChild(msgElement);
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
            }
        }
    </script>
</body>
</html>
