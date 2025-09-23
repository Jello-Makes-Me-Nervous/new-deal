
function validateBoxTypes() {

  var boxTypeName = trimstr(document.getElementById('boxTypeName').value);
  var categoryTypeId = trimstr(document.getElementById('categoryTypeId').value);

  if (boxTypeName.length < 1) {
    alert('Please input a Name');
    return false;
  }

  if (categoryTypeId == 0) {
    alert('Please select a Category');
    return false;
  }

  return true;

}

