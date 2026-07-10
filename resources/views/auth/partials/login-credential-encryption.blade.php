@if (!empty($loginPublicKey))
<script>
  (function () {
    const pem = @json($loginPublicKey);
    const alertMessage = @json(__('Unable to submit login credentials securely. Please try again.'));

    window.addEventListener('load', function () {
      const form = document.querySelector('form.login-form');
      const username = form?.querySelector('#username');
      const password = form?.querySelector('#password');
      const subtle = window.crypto?.subtle;

      if (!pem || !form || !username || !password || !subtle) {
        return;
      }

      const toBase64 = function (input) {
        const bytes = typeof input === 'string'
          ? new TextEncoder().encode(input)
          : new Uint8Array(input);
        let binary = '';

        bytes.forEach(function (byte) {
          binary += String.fromCharCode(byte);
        });

        return btoa(binary);
      };

      const importRsaKey = () => subtle.importKey(
        'spki',
        Uint8Array.from(atob(pem.replace(/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s/g, '')), (c) => c.charCodeAt(0)),
        { name: 'RSA-OAEP', hash: 'SHA-1' },
        false,
        ['encrypt']
      );

      const encryptCredentials = async function () {
        const payload = new TextEncoder().encode(JSON.stringify({
          u: username.value,
          p: password.value,
          t: Math.floor(Date.now() / 1000),
        }));
        const aesKey = await subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt']);
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encrypted = await subtle.encrypt({ name: 'AES-GCM', iv }, aesKey, payload);
        const encryptedKey = await subtle.encrypt(
          { name: 'RSA-OAEP' },
          await importRsaKey(),
          await subtle.exportKey('raw', aesKey)
        );

        return toBase64(JSON.stringify({
          v: 2,
          k: toBase64(encryptedKey),
          i: toBase64(iv),
          d: toBase64(encrypted),
        }));
      };

      form.addEventListener('submit', async function (event) {
        event.preventDefault();

        try {
          const encryptedCredentials = await encryptCredentials();

          username.removeAttribute('name');
          password.removeAttribute('name');

          const credentials = document.createElement('input');
          credentials.type = 'hidden';
          credentials.name = 'encrypted_credentials';
          credentials.value = encryptedCredentials;
          form.appendChild(credentials);

          const flag = document.createElement('input');
          flag.type = 'hidden';
          flag.name = 'encrypted';
          flag.value = '1';
          form.appendChild(flag);

          form.submit();
        } catch (error) {
          username.setAttribute('name', 'username');
          password.setAttribute('name', 'password');
          window.alert(alertMessage);
        }
      });
    });
  })();
</script>
@endif
