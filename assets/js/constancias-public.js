function updateEventId(select) {
    var eventId = select.value;
    document.getElementById('evento_id_hidden').value = eventId;
    
    if(eventId) {
        window.location.href = 'index.php?page=constancias&view=public&event_id=' + eventId;
    }
}

// Auto uppercase script
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.uppercase-input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    });
});