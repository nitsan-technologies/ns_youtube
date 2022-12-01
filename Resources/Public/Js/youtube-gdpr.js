function activateVideos(cookieValue,flag) {
  const iframes = document.querySelectorAll('.ns_video-gdpr iframe');
  document.cookie = cookieValue+"=1"; 
  iframes.forEach((iframe) => {
    if(flag == 1){
      if(cookieValue == iframe.dataset.src){
        iframe.src = iframe.dataset.src;
      } 
    } else if(flag == 0) {
      if(cookieValue == iframe.dataset.src){
        iframe.src = iframe.dataset.src;
      } else if(cookieValue == 'agreeToAll'){
        iframe.src = iframe.dataset.src;
      }
    } else {
      iframe.src = iframe.dataset.src;
    }
  });
}

function attachEvents() {
  
  const agreeToAll = document.querySelectorAll('.agreeToAll');
  agreeToAll.forEach((noticesBlock) => {
    var cookieCheck = getCookie('agreeToAll');
    if(cookieCheck != 1){
      noticesBlock.addEventListener('click', (event) => {
        activateVideos('agreeToAll',0);
        event.preventDefault();
      });
    } else {
      activateVideos('agreeToAll',3);
    }
  });

  const noticesBlocks = document.querySelectorAll('.ns_video-gdpr__notice-btn');
  noticesBlocks.forEach((noticesBlock) => {
    // var cookieCheck = getCookie(noticesBlock.parentNode.children[0].dataset.src);
    var cookieCheck = getCookie(noticesBlock.closest('.ns_video-gdpr').querySelector('iframe').dataset.src);
    if(cookieCheck != 1){
      noticesBlock.addEventListener('click', (event) => {
        activateVideos(noticesBlock.closest('.ns_video-gdpr').querySelector('iframe').dataset.src,0);
        event.preventDefault();
      });
    } else {
      activateVideos(noticesBlock.closest('.ns_video-gdpr').querySelector('iframe').dataset.src,1);
    }
  });
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
    c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
} 

attachEvents();