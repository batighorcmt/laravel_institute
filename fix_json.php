<?php
$data = [
  "type" => "service_account",
  "project_id" => "batighor-eims",
  "private_key_id" => "44d810344ba15ed71082df633b69c96779700d45",
  "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDLZM6Axl17HK8D\nl7nDdtwyM6+nCiBFSX+Ugy8yyZfCZODcWlohYFNqeeBOC2oMlmnq2VbDpFgpjktV\ngAYlf46OlwUUNQLS7soJSLV3Xv7EWBp2IPvqlvX7cig4FYvoSDY7Gv5AvQq9X9PN\nfZGPie8+Yz9EgB/RgbYpJEsh5UNld7fCbZcv3iXUhEEKwbxhx1MYBRvzv6mNWf3g\nTuHLxm/ZrnvM3oikgGD5pe3xkI0WFAqfZAI4lyhnrSXayucVLOnNS+Rw+sQzCWDD\nHdkrEZM37zkloxruKMIHu9z/tvLbJ19hy6unnMOIsiInukX54jiuXXMY8HlmswEe\n4fj+MU5fAgMBAAECggEALS5BaP2neleO0P99NlxK4aP1mud276059+WEMIXzfi0m\nfgxcBRn+raJRH0UN06n98TtCOMdjBigjt+RueRnyst39NNXpwK1ml9Vc6h1h/n+L\nsT32d8/86FQddwfiQkD4OvzuCbd0kUieJgGEuhvr1zH4teC4MLPdoRn8zpS8+ebO\nE7N+JFDAyIfo6HRRy9ZJz159G/p3D6Yqfc6ZtY/fGdXmnVJUUJhdDdaJemb7ZDt7\nz0zNB/SoUqfkKpbm4msu55Swmd4x3aUgf8jZOZHHmizfIjXy3Jrwe1z8UOOjRagh\nDyv9PcRdDaoc3Hkaz5nzXRUFEJur06LF6nYXwG1+UQKBgQD/vNnJu9oV/8pihpFn\n2hwmEZUZ924GDlL/MY/abPAZdw2yDpkoAwZaKwy+9H2b02L+LH+0+KqJ6OaXsD/M\nWxJ+pmKxQZuqG2XGJ4pfj80n2YDTs53otH4zj3Fufj0RdXbqHlPaEhVU3mC1cD1/\nmulVKpwCKb+27EEP7hJ0I8qWywKBgQDLmjZBBipOnxSC5Jo4vkAgKLkC9k/3p7ty\nrDHdwwsCi8JL/X1NunhqOmaNHwowVWLvoY0cPrvne/5R6X7x6Cyc+VaTDDkWlARI\ ZnI40Q8m5PJmcj9ZE/fFHkQJUyiQuvx7dzgnggROKwS+3N+UPeY7Wa+biAZfYeLY\nO9GOF/YgPQKBgCQ0WijDHWbL5Zz5LBClwiJpjtA0RTE6Semy3Iixr3QLdLbmdixB\n8V5GhfFqr3WmAXe2WQYHxizr+ekznHST2BRx/brWbPEyG8BnzKEmvIaNtj1CSnKt\SsiF4CsiWAbmuEUAsiJHORnguZQ2JZ6xEaNq+tcbjfaZj8RvkdE7V+ztAoGAYVdw\nWvQ8o+wwnUf4Z+qls/pkQgNLRkdeOjwTd0f56pSxJy2hi9j8GyWixBO0Blm8fH8L\nfRaNNeY1OCpfc/6h3cbj2lf8r0vDJG4+a+WfzeOl9M7odIk0a3zF4INuoIuVjN35\n7gGeV1T/5tSo3DIf8f42FDEbgtzE3Mb0sG/KT60CgYAPih9NGsPNvEfYrr3ph5DC\nDppiHsqJeYuu9y65MiEfx2WnG3qGZQEjBRbV3rlGzMUQk2asd5My2k/pNpHnWZyr\niOtABsAiF7kCKNAVmEwJxRTwgh7rApw5U64aX7U/Z/MRG6T/qP8ZOAYr7PAt9wnn\nhboC8SwzMbIc4cjZ4pDXkA==\n-----END PRIVATE KEY-----\n",
  "client_email" => "firebase-adminsdk-fbsvc@batighor-eims.iam.gserviceaccount.com",
  "client_id" => "106369957460386046578",
  "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
  "token_uri" => "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40batighor-eims.iam.gserviceaccount.com",
  "universe_domain" => "googleapis.com"
];
file_put_contents('storage/app/firebase-service-account.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "File written successfully with size " . filesize('storage/app/firebase-service-account.json');
