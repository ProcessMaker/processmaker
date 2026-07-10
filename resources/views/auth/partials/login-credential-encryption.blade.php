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

      const importKey = () => subtle.importKey(
        'spki',
        Uint8Array.from(atob(pem.replace(/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s/g, '')), (c) => c.charCodeAt(0)),
        { name: 'RSA-OAEP', hash: 'SHA-1' },
        false,
        ['encrypt']
      );

      form.addEventListener('submit', async function (event) {
        event.preventDefault();

        try {
          const encrypted = await subtle.encrypt(
            { name: 'RSA-OAEP' },
            await importKey(),
            new TextEncoder().encode(JSON.stringify({
              u: username.value,
              p: password.value,
              t: Math.floor(Date.now() / 1000),
            }))
          );

          username.removeAttribute('name');
          password.removeAttribute('name');

          const credentials = document.createElement('input');
          credentials.type = 'hidden';
          credentials.name = 'encrypted_credentials';
          credentials.value = btoa(String.fromCharCode(...new Uint8Array(encrypted)));
          form.appendChild(credentials);

          const flag = document.createElement('input');
          flag.type = 'hidden';
          flag.name = 'encrypted';
          flag.value = '1';
          form.appendChild(flag);

          form.submit();
        } catch (error) {
          window.alert(alertMessage);
        }
      });
    });
  })();
</script>
@endif
