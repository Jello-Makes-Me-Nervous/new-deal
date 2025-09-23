function toggleInactives() {
    var showit = document.getElementById('showinactive').checked;
    if (showit) {
        $('.inactivelisting').show();
    } else {
        $('.inactivelisting').hide();
    }
}

function checkYear1() {
  var year1 = document.getElementById('year1').value;
  if (typeof year1 !== 'undefined') {
    if(year1.match(/^[0-9]{2}[/][0-9]{1}$/)){
      return true;
    } else {
      alert('Accepted Format for Year: 00/0');
      return false;
    }
  }
}

function checkYear2() {
  var year2 = document.getElementById('year2').value;
  if (typeof year2 !== 'undefined') {
    if(year2.match(/^[0-9]{4}$/)){
      return true;
    } else {
      alert('Accepted Format for Year: 0000');
      return false;
    }
  }
}

function checkUOM() {
  var uom = document.getElementById('uom').value;
  var bpc = parseInt(document.getElementById('boxespercase').value);
  if (uom === 'case' &&  bpc <= 0 || uom === 'case' && isNaN(bpc)) {
    alert('Enter # of Boxes Per Case');
    return false;
  }
}