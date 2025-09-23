
function trimstr(stringToTrim) {

	return stringToTrim.replace(/^\s+|\s+$/g, '');
}

function checkMoney() {
  var dollars = trimstr(document.getElementById('dollars').value);
  var dollar = dollars.replace(/^\s+|\s+$/g,'');
  var regex = /^\d+$/;
  var isValid = regex.test(dollar);
  if (!isValid) {
    alert('Please add a dollar amount using digits');
    return false;
  }

  var cents = trimstr(document.getElementById('cents').value);
  if(cents.length == 0) {
  	cents.value = 00;
  } else {
    var cent = cents.replace(/^\s+|\s+$/g,'');
    var regex = /^\d{2}$/;
    var isValid = regex.test(cent);
    if (!isValid) {
      alert('Please use 2 digits only');
      return false;
    }
  }

}