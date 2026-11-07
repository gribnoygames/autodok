document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Находим форму и блок с причиной отмены внутри этой же карточки
            const form = this.closest('form');
            const reasonBlock = form.querySelector('.cancel-reason-block');
            const reasonInput = reasonBlock.querySelector('input');
            
            if (this.value === 'cancelled') {
                reasonBlock.style.display = 'block';
                reasonInput.required = true;
            } else {
                reasonBlock.style.display = 'none';
                reasonInput.required = false;
                reasonInput.value = ''; // Очищаем поле, если передумали отменять
            }
        });
    });
});