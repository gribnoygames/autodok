document.addEventListener('DOMContentLoaded', function() {
    // Логика показа поля причины отмены
    const statusSelects = document.querySelectorAll('.status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const reasonBlock = form.querySelector('.cancel-reason-block');
            const reasonInput = reasonBlock.querySelector('input');
            
            if (this.value === 'cancelled') {
                reasonBlock.style.display = 'block';
                reasonBlock.style.opacity = 0;
                // Небольшая анимация появления
                setTimeout(() => { reasonBlock.style.transition = 'opacity 0.3s'; reasonBlock.style.opacity = 1; }, 10);
                reasonInput.required = true;
            } else {
                reasonBlock.style.display = 'none';
                reasonInput.required = false;
                reasonInput.value = ''; 
            }
        });
    });

    // Микроанимация для кнопок: добавление "волны" при клике
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('mousedown', () => btn.style.transform = 'scale(0.96)');
        btn.addEventListener('mouseup', () => btn.style.transform = 'scale(1)');
        btn.addEventListener('mouseleave', () => btn.style.transform = 'scale(1)');
    });
});