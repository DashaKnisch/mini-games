(function () {
    const hintContainerId = 'hint';

    const typeHints = {
        js: `
            <h2>Инструкция — JavaScript</h2>
            <p>Загрузите ZIP с вашей JS-игрой (ZIP-архив обязателен для загрузки на сайт). Рекомендации:</p>
            <ol>
                <li> В корне архива должен быть index.html. Именно он будет загружаться на сайте.</li>
                <li> Рекомендуется упаковывать файлы прямо в корень ZIP (без лишних вложенных папок).</li>
                <li> Все ресурсы (JS/CSS/изображения) должны быть подключены относительными путями.</li>
                <li> Сохраняйте все файлы вашей игры в формате UTF-8 без BOM (без сигнатуры), чтобы русский текст отображался корректно.</li>
            </ol>
            <p>Если вы хотите передавать результаты игры на сайт (например, очки, прогресс, победа/поражение), используйте универсальный код:</p>
            <pre>
// Универсальная функция для отправки результатов игры 
function sendGameResult(score, meta = null) { 
    window.parent.postMessage({ 
        type: "game_result", 
        score: score, 
        meta: meta
    }, "*");
} 

sendGameResult(score); // отправляет результат на сайт
            </pre>
        `,
        unity: `
            <h2>Инструкция — Unity Web</h2>
            <p>Для загрузки Unity WebGL используйте ZIP с собранным WebGL-билдом (ZIP-архив обязателен для загрузки на сайт). Рекомендации:</p>
            <ol>
                <li> В Build Profile выберите профиль сборки, подходящий для веба (Web или похожий, зависит от версии).</li>
                <li> В Player Settings -> Publishing Settings выберите Compression Format -> Gzip/Disabled.</li>
                <li> Рекомендуется упаковывать файлы прямо в корень ZIP (без лишних вложенных папок).</li>
                <li> В ZIP ожидаются файлы примерно такой структуры (пример):</li>
            </ol>
            <pre>
/index.html
/Build/<имя_проекта>.framework.js
/Build/<имя_проекта>.wasm
/Build/<имя_проекта>.data.unityweb
/Build/<имя_проекта>.wasm.unityweb
/TemplateData/...
            </pre>
            <ol start="5">
                <li> Убедитесь, что index.html корректно ссылается на файлы в папке Build/ и TemplateData/.</li>
                <li> Рекомендуется помещать index.html в корень ZIP, но система может рекурсивно искать index.html в подпапках.</li>
                <li>! Обратите внимание на размер сборки — он может быть значительно больше, чем у обычной JS-игры.</li>
            </ol>
            <p>Функция отправки результатов на сайт:</p>
            <pre>
using UnityEngine;

public class GameResultSender : MonoBehaviour
{
    // Вызов из любого скрипта для отправки очков на сайт
    public void SendResult(int score, string meta = null)
    {
        string metaStr = meta != null ? meta : "null";
        string js = $@"
            window.parent.postMessage({{
                type: 'game_result',
                score: {score},
                meta: {metaStr}
            }}, '*');
        ";
        #if UNITY_WEBGL && !UNITY_EDITOR
        Application.ExternalEval(js);
        #else
        Debug.Log($""Game result: {score}, meta: {metaStr}"");
        #endif
    }
}
            </pre>
        `
    };


    const fieldHints = {
        title: '<h2>Название</h2><p>Придумайте понятное название для вашей игры. Это поле обязательно!</p>',
        rules: '<h2>Правила</h2><p>Опишите правила игры — что нужно сделать игроку, какие есть условия победы/поражения.Это поле обязательно!</p>',
        description: '<h2>Описание</h2><p>Короткое описание игры (опционально). Можно упомянуть управление и цель.</p>',
        icon: '<h2>Иконка</h2><p>Загрузите картинку (png/jpg). Она будет использована как превью игры.</p>',
        zip: '<h2>ZIP с игрой</h2><p>Загрузите ZIP-архив с файлами игры. Для Unity — ZIP должен содержать собранный WebGL-билд. Это поле обязательно!</p>',
        engine: '<h2>Тип игры</h2><p>Выберите, загружаете ли вы обычную JS-игру или Unity WebGL.</p>'
    };

    function $(selector, ctx = document) {
        return ctx.querySelector(selector);
    }
    function $all(selector, ctx = document) {
        return Array.from(ctx.querySelectorAll(selector));
    }

    function renderHint(html) {
        const node = document.getElementById(hintContainerId);
        if (!node) return;
        node.innerHTML = html || '';
    }

    function showTypeHintForCurrentSelection() {
        const sel = $('select[name="engine"]');
        if (!sel) return;
        const value = sel.value === 'unity' ? 'unity' : 'js';
        renderHint(typeHints[value]);
    }

    function init() {
        renderHint('');

        const selectEngine = $('select[name="engine"]');
        const form = document.querySelector('.game-form');

        if (!form || !selectEngine) return;

        selectEngine.addEventListener('change', function () {
            showTypeHintForCurrentSelection();
        });

        const interactive = $all('.game-form input, .game-form textarea, .game-form select');

        interactive.forEach(el => {
            el.addEventListener('focus', function () {
                const name = el.name;
                if (fieldHints[name]) {
                    renderHint(fieldHints[name]);
                } else {
                    showTypeHintForCurrentSelection();
                }
            });

            el.addEventListener('blur', function () {
                setTimeout(function () {
                    if (document.activeElement && form.contains(document.activeElement)) {
                        const active = document.activeElement;
                        if (active && active.name && fieldHints[active.name]) {
                            renderHint(fieldHints[active.name]);
                            return;
                        }
                    }
                    showTypeHintForCurrentSelection();
                }, 50);
            });
        });

        const hintNode = document.getElementById(hintContainerId);
        if (hintNode) {
            hintNode.addEventListener('click', function (e) {
            });
        }

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
