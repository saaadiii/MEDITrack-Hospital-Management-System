<?php
class AdminModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function stats()
    {
        $q = "SELECT
      (SELECT COUNT(*) FROM patients WHERE status='Active') patients,
      (SELECT COUNT(*) FROM doctors WHERE status='Active') doctors,
      (SELECT COUNT(*) FROM appointments WHERE status='Booked') appointments,
      (SELECT COALESCE(SUM(amount),0) FROM billing WHERE type='Patient Charge') revenue,
      (SELECT COALESCE(SUM(amount-paid),0) FROM billing WHERE type='Patient Charge') due,
      (SELECT COUNT(*) FROM inventory WHERE quantity<=minimum_level) low_stock";
        return mysqli_fetch_assoc(mysqli_query($this->c, $q));
    }

     function dashboardSummary()
     {
         $start = date('Y-m-01');
         $end = date('Y-m-01', strtotime('first day of next month'));
         $today = date('Y-m-d');
         $sql =
             'SELECT (SELECT COUNT(*) FROM patients WHERE created_at>=? AND ' .
             "created_at<?) monthly_patients,
    (SELECT COALESCE(SUM(paid),0) FROM " .
             "billing WHERE type='Patient Charge' AND transaction_date>=? AND " .
             'transaction_date<?) monthly_revenue';
         $s = mysqli_prepare($this->c, $sql);
         mysqli_stmt_bind_param($s, 'ssss', $start, $end, $start, $end);
         mysqli_stmt_execute($s);
         $summary = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
         mysqli_stmt_close($s);
         $s = mysqli_prepare(
             $this->c,
             'SELECT status,COUNT(*) total FROM appointments WHERE appointment_date=? GROUP BY status'
         );
         mysqli_stmt_bind_param($s, 's', $today);
         mysqli_stmt_execute($s);
         $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
         mysqli_stmt_close($s);
         $summary['today'] = [
             'total' => 0,
             'Completed' => 0,
             'Cancelled' => 0,
             'Booked' => 0,
             'Did not appear' => 0
         ];
         foreach ($rows as $row) {
             $summary['today'][$row['status']] = (int) $row['total'];
             $summary['today']['total'] += (int) $row['total'];
         }
         return $summary;
     }

      function recentActivity()
      {
          // Only timestamped events are shown; appointment completion has no event timestamp.
          $sql =
              "SELECT * FROM (
     (SELECT 'patient' kind,id event_id,'Patient registered' " .
              'title,name detail,created_at event_time FROM patients ORDER BY created_at ' .
              "DESC,id DESC LIMIT 6)
     UNION ALL
     (SELECT 'appointment' kind,a.id " .
              "event_id,'Appointment booked' title,CONCAT(p.name,' · '," .
              "DATE_FORMAT(a.appointment_date,'%d %b %Y'),' '," .
              "TIME_FORMAT(a.appointment_time,'%h:%i %p')) detail,a.created_at event_time " .
              'FROM appointments a JOIN patients p ON p.id=a.patient_id ORDER BY ' .
              "a.created_at DESC,a.id DESC LIMIT 6)
     UNION ALL
     (SELECT 'stock' kind," .
              "id event_id,IF(action_type='Created','Stock item added','Stock updated') " .
              "title,CONCAT(COALESCE(new_item_name,old_item_name),' · '," .
              "COALESCE(new_quantity,0),' units') detail,changed_at event_time FROM " .
              "inventory_history ORDER BY changed_at DESC,id DESC LIMIT 6)
     UNION " .
              "ALL
     (SELECT 'billing' kind,id event_id,'Patient billing updated' title," .
              "CONCAT(description,' · BDT ',FORMAT(paid,2),' paid') detail," .
              'COALESCE(updated_at,created_at) event_time FROM billing WHERE ' .
              "type='Patient Charge' ORDER BY event_time DESC,id DESC LIMIT 6)
    ) " .
              'activity ORDER BY event_time DESC,kind,event_id DESC LIMIT 6';
          return mysqli_fetch_all(mysqli_query($this->c, $sql), MYSQLI_ASSOC);
      }

}
