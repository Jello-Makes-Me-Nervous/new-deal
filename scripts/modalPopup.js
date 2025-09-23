function makeModalPopup(modalId) {
    var modal = document.getElementById('myModal'+modalId);
    var link = document.getElementById('modalLink'+modalId);
    var span = document.getElementsByClassName('ModalClose'+modalId)[0];

    link.onclick = function() {
        console.log(link);
        modal.style.display = 'block';
    };

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = 'none';
    };

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

}