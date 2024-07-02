import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getDatabase, ref, get, child } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";
import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyB8smbF627Xh_zZiagvIPqrKaoSlDumTJ4",
  authDomain: "fsms-48cb1.firebaseapp.com",
  databaseURL: "https://fsms-48cb1-default-rtdb.firebaseio.com",
  projectId: "fsms-48cb1",
  storageBucket: "fsms-48cb1.appspot.com",
  messagingSenderId: "693339377786",
  appId: "1:693339377786:web:a363ef6c5edc94e3a86bc3",
  measurementId: "G-VYE6B01M2N"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getDatabase(app);
const auth = getAuth(app);
const dbref = ref(db);

document.getElementById('loginForm1').addEventListener('submit', event => {
  event.preventDefault();
  const email = document.getElementById('email1').value;
  const password = document.getElementById('password1').value;
  handleLogin(email, password, 'indexemployee.html');
});

document.getElementById('loginForm2').addEventListener('submit', event => {
  event.preventDefault();
  const email = document.getElementById('email2').value;
  const password = document.getElementById('password2').value;
  handleLogin(email, password, 'index.html');
});

function handleLogin(email, password, defaultRedirect) {
  signInWithEmailAndPassword(auth, email, password)
    .then((credentials) => {
      get(child(dbref, 'UserAuthList/' + credentials.user.uid)).then((snapshot) => {
        if (snapshot.exists()) {
          const userData = snapshot.val();
          sessionStorage.setItem("user-info", JSON.stringify({
            firstname: userData.firstname,
            lastname: userData.lastname,
            isAdmin: userData.isAdmin
          }));
          sessionStorage.setItem("user-creds", JSON.stringify(credentials.user));
          
          if (userData.isAdmin) {
            window.location.href = 'adminpanel.html';
          } else {
            window.location.href = defaultRedirect;
          }
        } else {
          console.log('No data available');
        }
      }).catch((error) => {
        console.error(error);
      });
    })
    .catch((error) => {
      alert(error.message);
      console.log(error.code);
      console.log(error.message);
    });
}
