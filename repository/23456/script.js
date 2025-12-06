let score = 0;
let time = 5;
let timerId = null;

const clickBtn = document.getElementById("clickBtn");
const scoreEl = document.getElementById("score");
const timerEl = document.getElementById("timer");
const resultEl = document.getElementById("result");

// Создаём кнопку "Начать"
const startBtn = document.createElement("button");
startBtn.textContent = "Начать";
startBtn.id = "startBtn";
clickBtn.parentNode.insertBefore(startBtn, clickBtn);

clickBtn.disabled = true;

// Универсальная функция для отправки результатов
function sendGameResult(score, meta = null) {
    window.parent.postMessage({
        type: "game_result",
        score: score,
        meta: meta
    }, "*");
}

// Обработчик кнопки "Начать"
startBtn.addEventListener("click", () => {
    startBtn.style.display = "none";
    clickBtn.disabled = false;
    timerEl.textContent = time;

    timerId = setInterval(() => {
        time--;
        timerEl.textContent = time;

        if (time <= 0) {
            clearInterval(timerId);
            clickBtn.disabled = true;
            resultEl.textContent = "Игра окончена! Ваш результат: " + score;

            // Отправляем результат через универсальную функцию
            sendGameResult(score);
        }
    }, 1000);
});

// Логика клика
clickBtn.addEventListener("click", () => {
    score++;
    scoreEl.textContent = score;
});
