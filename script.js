function addMessage(text, sender) {
  const chatBox = document.getElementById("chat-box");
  const msg = document.createElement("div");
  msg.classList.add("message", sender);
  msg.innerText = text;
  chatBox.appendChild(msg);
  chatBox.scrollTop = chatBox.scrollHeight;
}

function botReply(message) {
  let response = "";

  if (message.includes("html")) {
    response = "HTML sert à structurer une page web.";
  } 
  else if (message.includes("css")) {
    response = "CSS permet de styliser ton site.";
  } 
  else if (message.includes("javascript")) {
    response = "JavaScript rend ton site interactif.";
  }
  else if (message.includes("site")) {
    response = "Pour créer un site, commence par HTML, puis CSS et JavaScript.";
  }
  else {
    response = "Je suis Technova AI 🤖 Pose-moi une question en informatique !";
  }

  setTimeout(() => {
    addMessage(response, "bot");
    speak(response);
  }, 500);
}

function sendMessage() {
  const input = document.getElementById("user-input");
  const message = input.value.toLowerCase();

  if (message.trim() === "") return;

  addMessage(input.value, "user");
  botReply(message);
  input.value = "";
}

// 🎤 VOIX (speech recognition)
function startVoice() {
  const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
  recognition.lang = "fr-FR";

  recognition.onresult = function(event) {
    const voiceText = event.results[0][0].transcript;
    document.getElementById("user-input").value = voiceText;
    sendMessage();
  };

  recognition.start();
}

// 🔊 VOIX IA (text-to-speech)
function speak(text) {
  const speech = new SpeechSynthesisUtterance(text);
  speech.lang = "fr-FR";
  window.speechSynthesis.speak(speech);
}
