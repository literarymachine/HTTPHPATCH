Installation
------------

    $ git clone --recursive git@github.com:literarymachine/HTTPHPATCH.git
    $ cd HTTPHPATCH
    $ mkdir resource
    $ git init resource/
    $ chmod -R o+w resource/
    $ cd resource/
    $ git config user.name "HTTP PATCHer"
    $ git config user.email "http.patcher@ex.org"

Test
----

    $ echo -e '
    <http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/givenName> "Petr" .
    <http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/familyName> "Griffin" .
    ' | curl -i -XPUT --data-binary @- http://localhost/HTTPHPATCH/resource/1
    HTTP/1.1 200 OK

    $ curl -i -XGET http://localhost/HTTPHPATCH/resource/1
    HTTP/1.1 200 OK
    Date: Sat, 30 Nov 2013 15:16:11 GMT
    Server: Apache/2.2.22 (Ubuntu)
    Last-Modified: Sat, 30 Nov 2013 15:13:00 GMT
    ETag: "1de0885-a4-4ec6661e77cd0"
    Accept-Ranges: bytes
    Content-Length: 164

    <http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/givenName> "Petr" .
    <http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/familyName> "Griffin" .

    $ echo -e '
    -<http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/givenName> "Petr" .
    +<http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/givenName> "Peter" .
    ' | curl -i -XPATCH --data-binary @- http://localhost/HTTPHPATCH/resource/1
    HTTP/1.1 204 No Content

    $ curl -i -XGET http://localhost/HTTPHPATCH/resource/1
    HTTP/1.1 200 OK
    Date: Sat, 30 Nov 2013 15:17:31 GMT
    Server: Apache/2.2.22 (Ubuntu)
    Last-Modified: Sat, 30 Nov 2013 15:16:52 GMT
    ETag: "1de0885-a5-4ec666fc62589"
    Accept-Ranges: bytes
    Content-Length: 165

    <http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/familyName> "Griffin" .
    <http://localhost/HTTPHPATCH/resource/1#thing> <http://xmlns.com/foaf/0.1/givenName> "Peter" .

    $ git log --pretty=oneline
    84f90bf9ee0cfa6f232a6079b66a3ec78f562680 1
    41a26a3e6451312a2c18af56d37e5d4400149122 1
