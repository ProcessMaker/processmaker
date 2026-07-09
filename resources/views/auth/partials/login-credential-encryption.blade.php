@if (!empty($loginPublicKey))
<meta name="login-public-key" content="{{ $loginPublicKey }}">
<script>
  (function () {
    const publicKeyMeta = document.querySelector('meta[name="login-public-key"]');
    const form = document.querySelector('form.form[action="{{ route('login') }}"]');

    if (!publicKeyMeta || !form || !window.crypto || !window.crypto.subtle) {
      return;
    }

    const usernameInput = form.querySelector('#username');
    const passwordInput = form.querySelector('#password');

    if (!usernameInput || !passwordInput) {
      return;
    }

    function pemToArrayBuffer(pem) {
      const contents = pem
        .replace('-----BEGIN PUBLIC KEY-----', '')
        .replace('-----END PUBLIC KEY-----', '')
        .replace(/\s/g, '');
      const binary = atob(contents);
      const bytes = new Uint8Array(binary.length);

      for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
      }

      return bytes.buffer;
    }

    async function importPublicKey(pem) {
      return window.crypto.subtle.importKey(
        'spki',
        pemToArrayBuffer(pem),
        { name: 'RSA-OAEP', hash: 'SHA-1' },
        false,
        ['encrypt']
      );
    }

    async function encryptCredentials(publicKeyPem, username, password) {
      const key = await importPublicKey(publicKeyPem);
      const payload = JSON.stringify({
        u: username,
        p: password,
        t: Math.floor(Date.now() / 1000),
      });
      const encoded = new TextEncoder().encode(payload);
      const encrypted = await window.crypto.subtle.encrypt(
        { name: 'RSA-OAEP' },
        key,
        encoded
      );
      const bytes = new Uint8Array(encrypted);
      let binary = '';

      bytes.forEach(function (byte) {
        binary += String.fromCharCode(byte);
      });

      return btoa(binary);
    }

    function ensureHiddenField(name) {
      let field = form.querySelector('[name="' + name + '"]');

      if (!field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = name;
        form.appendChild(field);
      }

      return field;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const submit = async function () {
        const encryptedCredentials = await encryptCredentials(
          publicKeyMeta.content,
          usernameInput.value,
          passwordInput.value
        );

        usernameInput.removeAttribute('name');
        passwordInput.removeAttribute('name');
        usernameInput.value = '';
        passwordInput.value = '';

        ensureHiddenField('encrypted_credentials').value = encryptedCredentials;
        ensureHiddenField('encrypted').value = '1';

        form.submit();
      };

      submit().catch(function () {
        usernameInput.setAttribute('name', 'username');
        passwordInput.setAttribute('name', 'password');
        window.alert(@json(__('Unable to submit login credentials securely. Please try again.')));
      });
    });
  })();
</script>
@endif
