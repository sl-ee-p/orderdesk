// Quote form: add and remove line-item rows without a framework.
const lines = document.getElementById('lines');

if (lines) {
    const reindex = () => {
        lines.querySelectorAll('.line').forEach((row, i) => {
            row.querySelectorAll('[name^="items["]').forEach((field) => {
                field.name = field.name.replace(/^items\[\d+\]/, `items[${i}]`);
            });
        });
    };

    document.getElementById('add-line').addEventListener('click', () => {
        const row = lines.querySelector('.line').cloneNode(true);
        row.querySelector('select').value = '';
        row.querySelector('input').value = 1;
        lines.appendChild(row);
        reindex();
    });

    lines.addEventListener('click', (event) => {
        if (event.target.matches('[data-remove]') && lines.querySelectorAll('.line').length > 1) {
            event.target.closest('.line').remove();
            reindex();
        }
    });
}
