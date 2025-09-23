
function validPass() {

  var password = trimstr(document.getElementById('password').value);
  var newPassword = trimstr(document.getElementById('newPassword').value);
  var confirmPassword = trimstr(document.getElementById('confirmPassword').value);

  if (newPassword != confirmPassword) {
  	alert("Passwords do not match");
  	return false;
  }

  return true;

}

function confirmPassword() {

  var password = trimstr(document.getElementById('userPass').value);
  var password2 = trimstr(document.getElementById('userPass2').value);

  if (password != password2) {
  	alert("Passwords do not match");
  	return false;
  }
  var pass = trimstr(document.getElementById('userPass').value);
  if(pass.length < 1) {
  	alert('Password is is required');
  	document.getElementById('userPass').focus();
  	return false;
  }
}

function updatePassword() {

  var password = trimstr(document.getElementById('userPass').value);
  var password2 = trimstr(document.getElementById('userPass2').value);


  if (password.length == 0 && password2 == 0) {
  	return true;
  } else if (password != password2) {
  	alert("Passwords do not match");
  	return false;
  }
}
//need to do the password when we know what characters are allowed
/*
function validatePass(pass) {
  var regex = /^[A-Za-z0-9.!_\-]+$/; //allows upper lower digits .!_-
  var isValid = regex.test(pass);
  if (!isValid) {
  	alert('');
  }
}
*/