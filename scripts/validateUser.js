function validateUser() {

  var username = trimstr(document.getElementById('userName').value);
  if(username.length < 1) {
  	alert('User Name is is required');
  	document.getElementById('userName').focus();
  	return false;
  }
  var hint = trimstr(document.getElementById('hint').value);
  if(hint.length < 1) {
  	alert('Hint Question is required');
  	document.getElementById('hint').focus();
  	return false;
  }
  var answer = trimstr(document.getElementById('answer').value);
  if(answer.length < 1) {
    alert('Hint Answer is required');
  	document.getElementById('answer').focus();
  	return false;
  }
  var firstName = trimstr(document.getElementById('firstName').value);
  if(firstName.length < 1) {
    alert('First Name is required');
  	document.getElementById('firstName').focus();
  	return false;
  }
  var lastName = trimstr(document.getElementById('lastName').value);
  if(lastName.length < 1) {
    alert('Last Name is required');
  	document.getElementById('lastName').focus();
  	return false;
  }
  var companyName = trimstr(document.getElementById('companyName').value);
  if(companyName.length < 1) {
    alert('Company Name is required');
  	document.getElementById('companyName').focus();
  	return false;
  }
  var title = trimstr(document.getElementById('title').value);
  if(title.length < 1) {
    alert('Title is required');
  	document.getElementById('title').focus();
  	return false;
  }
  var street = trimstr(document.getElementById('street').value);
  if(street.length < 1) {
    alert('Address is required');
  	document.getElementById('street').focus();
  	return false;
  }
  var city = trimstr(document.getElementById('city').value);
  if(city.length < 1) {
    alert('City is required');
  	document.getElementById('city').focus();
  	return false;
  }
  var state = trimstr(document.getElementById('state').value);
  if(state.length < 1) {
    alert('State is required');
  	document.getElementById('state').focus();
  	return false;
  }
  var zip = trimstr(document.getElementById('zip').value);
  if(zip.length < 1) {
    alert('ZIP is required');
  	document.getElementById('zip').focus();
  	return false;
  }
  var country = trimstr(document.getElementById('country').value);
  if(country.length < 1) {
    alert('Country is required');
  	document.getElementById('').focus();
  	return false;
  }
  var phone = trimstr(document.getElementById('phone').value);
  if(phone.length < 1) {
    alert('Phone is required');
  	document.getElementById('country').focus();
  	return false;
  }
  var email = trimstr(document.getElementById('email').value);
  if(email.length < 1) {
    alert('Email  is required');
  	document.getElementById('email').focus();
  	return false;
  }
  var listingFee = trimstr(document.getElementById('listingFee').value);
  if(listingFee.length < 1) {
    alert('Listing Fee is required');
  	document.getElementById('listingFee').focus();
  	return false;
  }


}
