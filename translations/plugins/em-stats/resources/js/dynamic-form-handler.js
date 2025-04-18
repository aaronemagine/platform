document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('languages-settings-container');

    document.getElementById('add-language-button').addEventListener('click', function() {
        var index = container.children.length;
        var newRow = `<div class="language-setting-row">
            <input type="text" name="settings[languageSettings][${index}][code]" value="" placeholder="Code">
            <input type="text" name="settings[languageSettings][${index}][name]" value="" placeholder="Name">
            <input type="text" name="settings[languageSettings][${index}][color]" value="" placeholder="Color class">
            <input type="text" name="settings[languageSettings][${index}][hex]" value="" placeholder="HEX">
            <button type="button" class="remove-language-button">Remove</button>
        </div>`;
        container.insertAdjacentHTML('beforeend', newRow);
    });

    container.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-language-button')) {
            event.target.parentNode.remove();
        }
    });
});