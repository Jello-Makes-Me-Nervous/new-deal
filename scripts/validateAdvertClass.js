
function validateAdvertClass() {

  var advertClassName = trimstr(document.getElementById('advertClassName').value);
  var maxHeight = trimstr(document.getElementById('maxHeight').value);
  var maxWidth = trimstr(document.getElementById('maxWidth').value);

  if (advertClassName.length < 1) {
    alert('Please input a Name');
    return false;
  }

  return true;

}
