

function toggle() {
  var x = document.getElementById('detail-filters');
  x.classList.toggle('hide');
}



//* Toggle between adding and removing the 'responsive' class to topnav when the user clicks on the icon */
function responsiveMenu() {
  var x = document.getElementById('nav-primary');
  if (x.className === 'primary-menu') {
    x.className += ' responsive';
  } else {
    x.className = 'primary-menu';
  }
}



/* Toggle between adding and removing the "responsive" class to topnav when the user clicks on the icon */
function responsiveMenu() {
  var x = document.getElementById("nav-primary");
  if (x.className === "primary-menu") {
    x.className += " responsive";
  } else {
    x.className = "primary-menu";
  }
}

document.addEventListener('DOMContentLoaded', function() {
    window.onscroll = function() {stickyScroll()};
    var navbar = document.getElementById("nav-primary");
    var sticky = navbar.offsetTop;

    function stickyScroll() {
//    alert("scrolling: " + window.pageYOffset + " - " + sticky);
      if (window.pageYOffset >= sticky) {
        navbar.classList.add("sticky");
      } else {
        navbar.classList.remove("sticky");
      }
    }
});