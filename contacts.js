//Подключаем подсказки для некоторых полей ввода по готовности страницы
$(function () {
    $('<link/>', {
        rel: 'stylesheet',
        type: 'text/css',
        href: 'https://cdn.jsdelivr.net/jquery.suggestions/latest/css/suggestions.css'
    }).appendTo('head');
    $.getScript('https://cdn.jsdelivr.net/jquery.suggestions/latest/js/jquery.suggestions.min.js', function () {
        var serviceUrl="https://suggestions.dadata.ru/suggestions/api/4_1/rs",
            token = "---";
        $("#user_name").suggestions({
            serviceUrl: serviceUrl,
            token: token,
            type: "NAME",
            count: 5,
            triggerSelectOnSpace: false,
            hint: "",
            noCache: true,
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function(suggestion) {
            }
        });
        $("#user_email").suggestions({
            serviceUrl: serviceUrl,
            token: token,
            type: "EMAIL",
            count: 5,
            triggerSelectOnSpace: false,
            hint: "",
            noCache: true,
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function(suggestion) {
            }
        });
    });

});
