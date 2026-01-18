ΕΦΑΡΜΟΓΗ ΠΑΝΕΠΙΣΤΗΜΙΟΥ – ΜΕΡΟΣ Α’ (Prototype)

1) Απαιτήσεις
- XAMPP (Apache + MySQL/MariaDB)
- phpMyAdmin

2) Εγκατάσταση / Εκτέλεση
- Αντιγράψτε τον φάκελο "prototype" στο: C:\xampp\htdocs\
  (ή στο αντίστοιχο document root του web server)
- Εκκινήστε Apache και MySQL από το XAMPP Control Panel
- Ανοίξτε το phpMyAdmin και κάντε εισαγωγή (import) του αρχείου βάσης:
  prototype/database/university.sql
- Εκτελέστε την εφαρμογή από:
  http://localhost/prototype/

3) Εγγραφή Χρήστη (Role-based)
- Κωδικός εγγραφής Φοιτητή: STUD2025
- Κωδικός εγγραφής Καθηγητή: PROF2025

4) Τι να δοκιμάσετε 
- Η δημόσια αρχική σελίδα φορτώνει (responsive UI) και εμφανίζεται ο χάρτης Leaflet με marker
- Εγγραφή Φοιτητή και Καθηγητή (σωστή καταχώρηση στη βάση με το αντίστοιχο role_id)
- Σύνδεση με email + password (δημιουργία session: user_id, username, role_id)
- Σελίδες RBAC:
  - /student_area.php επιτρέπεται μόνο για Φοιτητές
  - /professor_area.php επιτρέπεται μόνο για Καθηγητές
  Μη εξουσιοδοτημένη πρόσβαση οδηγεί στη σελίδα /unauthorized.php
- Αποσύνδεση (logout) που καταστρέφει το session
