;!READLINE, in ASD is a very good fucntion, for checking lines
;!we can use READLINE(), at alone
;!READLINE()
;!or declareing it like a variable
;!SETVAR k READLINE()
;!we can even make a little program!

PRINT write your name!
SETVAR k READLINE()
IF k == "andrew" THEN DO
    PRINT good name Andrew 
ELSE
    PRINT good name =(k)
END