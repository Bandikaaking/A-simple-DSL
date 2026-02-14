SETVAR x yes

;! IF cond THEN DO
;!      code
;! ELSE;IF cond
;!        code
;! ELSE
;!      code
;! END

IF x == "yes" THEN DO
   PRINT "ok"
ELSE;IF y == "maybe"
   PRINT "hmm"
ELSE
   PRINT "fallback"
END
