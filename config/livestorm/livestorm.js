/**
 * On a livestorm.co webinar this will send commands from the browser's chat to Mercure
 * I used this at a conference to make !bravo play an "applause.mp3" file as Livestorm 
 * had no chat hook.
 */
(function() {
  try {
    const targetNode = document.getElementsByClassName('tchat-content')[0]
    const config = { attributes: false, childList: true, subtree: true };
    const callback = function(mutationsList, observer) {
      setTimeout(() => {
        for(let mutation of mutationsList) {
          if (mutation.type !== 'childList' || mutation.target.tagName !== 'DIV' || !mutation.addedNodes['0']) {
            continue;
          }

          if (typeof mutation.addedNodes['0'].querySelector !== 'function') {
            continue;
          }

          const msg = mutation.addedNodes['0'].querySelector('p.msg')

          if (!msg) {
            continue;
          }

          const data = (msg.outerText || msg.textContent || '').trim()

          console.log('got data', data)

          if (!data.startsWith('!')) {
            return;
          }

          const message = {message: data, nickname: 'unknown', channel: 'unknown'}
          fetch('http://localhost:8080/.well-known/mercure', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOltdfX0.GFRUFE2C1GaLTnX2WZnO3SoeOM0rrVcI0yph1K_Oo-w'}, body: `topic=https://app.livestorm.co/command/applause&data=${encodeURI(JSON.stringify(message))}`})
        }
      }, 1)
    };

    const observer = new MutationObserver(callback);
    observer.observe(targetNode, config);
  } catch (err) {
    console.error(err)
  }
})()