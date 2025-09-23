
function validateVersions() {
  var versionName = trimstr(document.getElementById('versionname').value);
  var versionDescription = trimstr(document.getElementById('versiondescription').value);

  if (versionName.length < 1) {
    alert('Please input a Name');
    return false;
  }
  if (versionDescription.length < 1) {
    alert('Please input a Description');
    return false;
  }

  return true;

}
