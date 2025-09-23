
function validVaction() {

    var startVaca = trimstr(document.getElementById('startVaca').value);
    var endVaca = trimstr(document.getElementById('endVaca').value);
    var regex = /^[0-9\/]+$/;
    var validStart = regex.test(startVaca);
    var validEnd = regex.test(endVaca);
    var isValid = true;
    if ((startVaca.length > 0) && (startVaca.length > 7)(startVaca.length < 11)) {
        alert("Start date must be mm/dd/yyyy or empty to turn off");
        isValid = false;
        if (!validStart) {
            alert('Invalid start date');
            isValid = false;
        }
    }

    if ((endVaca.length > 0) && (endVaca.length > 7) && (endVaca.length < 11)) {
        alert("End date must be mm/dd/yyyy or empty to turn off");
        isValid = false;
        if (!validEnd) {
            alert('Invalid end date');
            isValid = false;
        }
    }
 
  if (startVaca.length != endVaca.length) {
  	alert("Start and End dates are required or both empty to turn off vacation");
  	isValid = false;
  }

/*
 * var date_regex = /^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[01])\/(19|20)\d{2}$/;
if (!(date_regex.test(testDate))) {
    return false;
}
 */
  
  return isValid;
}
