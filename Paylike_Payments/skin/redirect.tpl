<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
</head>
<body>
  <style>
    .loader {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 100px;
      height: 100px;
      margin-top: -50px; /* half the height */
      margin-left: -50px; /* half the width */
      border: 16px solid #f3f3f3;
      border-top: 16px solid #2F5A34;
      border-radius: 50%;
      animation: spin 2s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
  
  <div class="loader"></div>

  <script>
  var form = document.createElement('form');
  form.method = 'post';
  form.action = 'index.php?_g=rm&type=gateway&cmd=process&module=Paylike_Payments&orderid={$ORDERID}&transactionid={$TXNID}';
  document.body.appendChild(form);
  form.submit();
  </script>
</body>
</html>
