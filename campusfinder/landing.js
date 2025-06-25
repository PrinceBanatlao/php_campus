document.addEventListener('DOMContentLoaded', () => {
  const loginBtn = document.getElementById('loginBtn');
  const reportBtn = document.getElementById('report-btn');
  const loginModal = document.getElementById('loginModal');
  const signupModal = document.getElementById('signupModal');
  const closeBtns = document.querySelectorAll('.close');
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');
  const showSignup = document.getElementById('showSignup');
  const showLogin = document.getElementById('showLogin');

  
  loginBtn.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'flex';
  });

  reportBtn.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'flex';
  });

  
  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      loginModal.style.display = 'none';
      signupModal.style.display = 'none';
      clearErrors();
    });
  });

  window.addEventListener('click', (e) => {
    if (e.target === loginModal || e.target === signupModal) {
      loginModal.style.display = 'none';
      signupModal.style.display = 'none';
      clearErrors();
    }
  });

  
  showSignup.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'none';
    signupModal.style.display = 'flex';
  });

  showLogin.addEventListener('click', (e) => {
    e.preventDefault();
    signupModal.style.display = 'none';
    loginModal.style.display = 'flex';
  });

  
  function clearErrors() {
    const errorEls = document.querySelectorAll('.error-message');
    errorEls.forEach(el => {
      el.textContent = '';
      el.style.display = 'none';
    });
    const notifications = document.querySelectorAll('.custom-notification');
    notifications.forEach(el => {
      el.textContent = '';
      el.style.display = 'none';
      el.style.opacity = '1';
      el.classList.remove('fade-out', 'success');
    });
  }

  
  function fadeOutNotification(notification, duration = 3000) {
    notification.style.display = 'block';
    notification.style.opacity = '1';
    setTimeout(() => {
      notification.classList.add('fade-out');
      setTimeout(() => {
        notification.style.display = 'none';
        notification.textContent = '';
        notification.classList.remove('fade-out', 'success');
        notification.style.opacity = '1';
      }, 500); 
    }, duration);
  }

  
  function showSuccessOverlay(message) {
    let overlay = document.querySelector('.success-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'success-overlay';
      const messageDiv = document.createElement('div');
      messageDiv.className = 'success-message';
      messageDiv.textContent = message;
      overlay.appendChild(messageDiv);
      document.body.appendChild(overlay);
    } else {
      overlay.querySelector('.success-message').textContent = message;
    }
    
    setTimeout(() => {
      overlay.classList.add('show');
      overlay.querySelector('.success-message').classList.add('show');
    }, 10);

    return overlay;
  }

  
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();

    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value.trim();
    let isValid = true;

    if (!email) {
      document.getElementById('loginEmailError').textContent = 'Email is required';
      document.getElementById('loginEmailError').style.display = 'block';
      isValid = false;
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
      document.getElementById('loginEmailError').textContent = 'Please enter a valid email';
      document.getElementById('loginEmailError').style.display = 'block';
      isValid = false;
    }

    if (!password) {
      document.getElementById('loginPasswordError').textContent = 'Password is required';
      document.getElementById('loginPasswordError').style.display = 'block';
      isValid = false;
    }

    if (isValid) {
      const formData = new FormData(loginForm);
      try {
        const response = await fetch('landing.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const text = await response.text();
          console.error('Non-JSON response:', text);
          throw new Error('Server returned invalid response format.');
        }
        const data = await response.json();
        const notification = document.getElementById('loginNotification');
        if (data.status === 'error') {
          notification.textContent = data.message;
          fadeOutNotification(notification);
          loginModal.style.display = 'flex';
        } else if (data.status === 'success') {
          notification.classList.add('success');
          notification.textContent = 'Login successful! Redirecting...';
          fadeOutNotification(notification, 2000);
          loginForm.reset();
          history.replaceState(null, '', 'landing.php');
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 2000);
        }
      } catch (error) {
        console.error('Login error:', error.message);
        const notification = document.getElementById('loginNotification');
        notification.textContent = 'An error occurred: ' + error.message;
        fadeOutNotification(notification);
        loginModal.style.display = 'flex';
      }
    }
  });

  
  signupForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();

    const username = document.getElementById('username').value.trim();
    const firstName = document.getElementById('firstName').value.trim();
    const middleName = document.getElementById('middleName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const password = document.getElementById('signupPassword').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();
    let isValid = true;

    if (!username) {
      document.getElementById('usernameError').textContent = 'Username is required';
      document.getElementById('usernameError').style.display = 'block';
      isValid = false;
    }

    if (!firstName) {
      document.getElementById('firstNameError').textContent = 'First name is required';
      document.getElementById('firstNameError').style.display = 'block';
      isValid = false;
    }

    if (!lastName) {
      document.getElementById('lastNameError').textContent = 'Last name is required';
      document.getElementById('lastNameError').style.display = 'block';
      isValid = false;
    }

    if (!email) {
      document.getElementById('signupEmailError').textContent = 'Email is required';
      document.getElementById('signupEmailError').style.display = 'block';
      isValid = false;
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
      document.getElementById('signupEmailError').textContent = 'Please enter a valid email';
      document.getElementById('signupEmailError').style.display = 'block';
      isValid = false;
    }

    if (!password) {
      document.getElementById('signupPasswordError').textContent = 'Password is required';
      document.getElementById('signupPasswordError').style.display = 'block';
      isValid = false;
    } else if (password.length < 6) {
      document.getElementById('signupPasswordError').textContent = 'Password must be at least 6 characters';
      document.getElementById('signupPasswordError').style.display = 'block';
      isValid = false;
    }

    if (password !== confirmPassword) {
      document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
      document.getElementById('confirmPasswordError').style.display = 'block';
      isValid = false;
    }

    if (isValid) {
      const formData = new FormData(signupForm);
      try {
        const response = await fetch('landing.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const text = await response.text();
          console.error('Non-JSON response:', text);
          throw new Error('Server returned invalid response format.');
        }
        const data = await response.json();
        const notification = document.getElementById('signupNotification');
        if (data.status === 'error') {
          notification.textContent = data.message;
          fadeOutNotification(notification);
          signupModal.style.display = 'flex';
        } else if (data.status === 'success') {
          signupModal.style.display = 'none';
          const overlay = showSuccessOverlay(data.message);
          signupForm.reset();
          history.replaceState(null, '', 'landing.php');
          setTimeout(() => {
            overlay.classList.remove('show');
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 500);
          }, 3000);
        }
      } catch (error) {
        console.error('Signup error:', error.message);
        const notification = document.getElementById('signupNotification');
        notification.textContent = 'An error occurred: ' + error.message;
        fadeOutNotification(notification);
        signupModal.style.display = 'flex';
      }
    }
  });

  
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href === '#' && this.getAttribute('data-target')) {
        e.preventDefault();
        const targetId = this.getAttribute('data-target');
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
          targetSection.scrollIntoView({ behavior: 'smooth' });
        }
      }
    });
  });
});