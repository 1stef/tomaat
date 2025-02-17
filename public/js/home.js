var images = ['../images/clubhuis.jpg', '../images/clubhuis2.jpg', '../images/clubhuis3.jpg'];
var index = 0;
var show_background = true;
setInterval(change_bg, 10000);
function change_bg(){
  if (show_background) {
    index = (index + 1 < images.length) ? index + 1 : 0;
    $('.bg').fadeOut(000, function(){
        $(this).css('background-image', 'url('+ images[index] + ')')
        $(this).fadeIn(000);
      }
    );
  } else {
    $(this).css('background-image', none);
  }
}