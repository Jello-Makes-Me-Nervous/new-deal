

function getMyTimezone () {

  var timesplit = new Date().toString().split(" ");
  var mytime = new Date().toString();
  var timeZoneFormatted = timesplit[timesplit.length - 2] + " " + timesplit[timesplit.length - 1];

  $('input[name="tz"]').val(mytime);
  $('input[name="tz2"]').val(mytime);

}


