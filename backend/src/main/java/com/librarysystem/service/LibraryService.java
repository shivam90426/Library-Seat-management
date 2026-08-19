package com.librarysystem.service;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.format.DateTimeParseException;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import org.springframework.http.HttpStatus;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.server.ResponseStatusException;

@Service
public class LibraryService {
    private final JdbcTemplate jdbc;

    public LibraryService(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    public Map<String, Object> userDashboard(long userId) {
        LocalDate today = LocalDate.now();
        Map<String, Object> result = new LinkedHashMap<>();
        result.put("subscription", activeSubscription(userId));
        result.put("activeSeat", mySeat(userId));
        result.put("activeTimer", first("SELECT entry_time FROM timings WHERE user_id=? AND exit_time IS NULL ORDER BY id DESC LIMIT 1", userId));
        result.put("todayHours", scalarDecimal("SELECT ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) FROM timings WHERE user_id=? AND DATE(entry_time)=?", userId, today));
        result.put("monthHours", scalarDecimal("SELECT ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) FROM timings WHERE user_id=? AND MONTH(entry_time)=MONTH(CURDATE()) AND YEAR(entry_time)=YEAR(CURDATE())", userId));
        result.put("diary", first("SELECT content, entry_date, updated_at FROM diary_entries WHERE user_id=? AND entry_date=? LIMIT 1", userId, today));
        return result;
    }

    public List<Map<String, Object>> seatsForUser(long userId) {
        LocalDate today = LocalDate.now();
        List<Map<String, Object>> rows = jdbc.queryForList("""
            SELECT s.id, s.seat_no, s.seat_type, s.section_name, s.position_order,
                   s.is_active, s.is_maintenance, sb.user_id AS booked_user
            FROM seats s
            LEFT JOIN seat_bookings sb ON sb.seat_id=s.id AND sb.status='active'
                AND ((sb.booking_type='daily' AND sb.booking_date=?) OR sb.booking_type='fixed')
            ORDER BY s.section_name, s.position_order, s.id
            """, today);
        for (Map<String, Object> row : rows) {
            boolean blocked = !asBoolean(row.get("is_active")) || asBoolean(row.get("is_maintenance"));
            Object bookedUser = row.get("booked_user");
            String status = blocked ? "blocked" : bookedUser == null ? "available"
                : ((Number) bookedUser).longValue() == userId ? "mine" : "booked";
            row.put("status", status);
        }
        return rows;
    }

    @Transactional
    public Map<String, Object> bookSeat(long userId, long seatId) {
        LocalDate today = LocalDate.now();
        Map<String, Object> subscription = activeSubscription(userId);
        if (subscription == null || LocalDate.parse(String.valueOf(subscription.get("end_date"))).isBefore(today)) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "Your subscription has expired.");
        }
        String bookingType = "6h".equals(subscription.get("seat_type")) ? "daily" : "fixed";
        Integer existing = jdbc.queryForObject("""
            SELECT COUNT(*) FROM seat_bookings WHERE user_id=? AND status='active'
            AND ((booking_type='daily' AND booking_date=?) OR booking_type='fixed')
            """, Integer.class, userId, today);
        if (existing != null && existing > 0) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "You already have an active seat booking.");
        }
        Integer taken = jdbc.queryForObject("""
            SELECT COUNT(*) FROM seat_bookings WHERE seat_id=? AND status='active'
            AND ((booking_type='daily' AND booking_date=?) OR booking_type='fixed')
            """, Integer.class, seatId, today);
        if (taken != null && taken > 0) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "This seat is already booked.");
        }
        int created = jdbc.update("""
            INSERT INTO seat_bookings (seat_id, user_id, subscription_id, booking_type, booking_date, booking_start, status)
            VALUES (?, ?, ?, ?, ?, NOW(), 'active')
            """, seatId, userId, ((Number) subscription.get("id")).longValue(), bookingType, today);
        if (created != 1) {
            throw new ResponseStatusException(HttpStatus.INTERNAL_SERVER_ERROR, "Unable to book the seat.");
        }
        return Map.of("status", "booked", "bookingType", bookingType);
    }

    public Map<String, Object> mySeat(long userId) {
        return first("""
            SELECT sb.booking_type, sb.booking_date, sb.booking_start, s.seat_no, s.seat_type, s.section_name,
                   sub.start_date, sub.end_date, sub.status AS subscription_status
            FROM seat_bookings sb JOIN seats s ON s.id=sb.seat_id
            LEFT JOIN subscriptions sub ON sub.id=sb.subscription_id
            WHERE sb.user_id=? AND sb.status='active'
              AND ((sb.booking_type='daily' AND sb.booking_date=CURDATE())
                   OR (sb.booking_type='fixed' AND sub.status='active' AND sub.end_date>=CURDATE()))
            ORDER BY CASE WHEN sb.booking_type='fixed' THEN 0 ELSE 1 END, sb.id DESC LIMIT 1
            """, userId);
    }

    @Transactional
    public Map<String, Object> startTimer(long userId) {
        Map<String, Object> subscription = activeSubscription(userId);
        if (subscription == null || LocalDate.parse(String.valueOf(subscription.get("end_date"))).isBefore(LocalDate.now())) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "Your subscription has expired.");
        }
        Integer open = jdbc.queryForObject("SELECT COUNT(*) FROM timings WHERE user_id=? AND exit_time IS NULL", Integer.class, userId);
        if (open != null && open > 0) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "Your study timer is already running.");
        }
        jdbc.update("INSERT INTO timings (user_id, entry_time) VALUES (?, NOW())", userId);
        return Map.of("status", "started");
    }

    @Transactional
    public Map<String, Object> stopTimer(long userId) {
        Map<String, Object> row = first("SELECT id, entry_time FROM timings WHERE user_id=? AND exit_time IS NULL ORDER BY id DESC LIMIT 1", userId);
        if (row == null) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "No active study timer was found.");
        }
        jdbc.update("UPDATE timings SET exit_time=NOW(), duration_minutes=GREATEST(TIMESTAMPDIFF(MINUTE, entry_time, NOW()), 0) WHERE id=?", ((Number) row.get("id")).longValue());
        return Map.of("status", "stopped");
    }

    public Map<String, Object> analytics(long userId) {
        Map<String, Object> summary = first("""
            SELECT ROUND(IFNULL(SUM(duration_minutes),0)/60,2) AS total_hours,
                ROUND(IFNULL(SUM(CASE WHEN MONTH(entry_time)=MONTH(CURDATE()) AND YEAR(entry_time)=YEAR(CURDATE()) THEN duration_minutes ELSE 0 END),0)/60,2) AS monthly_hours,
                COUNT(DISTINCT DATE(entry_time)) AS active_days
            FROM timings WHERE user_id=?
            """, userId);
        Map<String, Object> response = new LinkedHashMap<>();
        response.put("summary", summary);
        response.put("subscription", activeSubscription(userId));
        response.put("daily", usageByDay(userId));
        response.put("monthly", usageByMonth(userId));
        response.put("weekly", weeklyUsage(userId));
        return response;
    }

    public Map<String, Object> weeklyUsage(long userId) {
        LocalDate monday = LocalDate.now().minusDays(LocalDate.now().getDayOfWeek().getValue() - 1L);
        Map<String, Object> subscription = activeSubscription(userId);
        Map<String, Object> raw = new LinkedHashMap<>();
        for (Map<String, Object> row : jdbc.queryForList("""
            SELECT DATE(entry_time) AS day, ROUND(SUM(GREATEST(duration_minutes,0))/60,2) AS hours
            FROM timings WHERE user_id=? AND DATE(entry_time) BETWEEN ? AND ? GROUP BY DATE(entry_time)
            """, userId, monday, monday.plusDays(6))) {
            raw.put(String.valueOf(row.get("day")), row.get("hours"));
        }
        List<String> labels = new ArrayList<>();
        List<Object> hours = new ArrayList<>();
        for (int i = 0; i < 7; i++) {
            LocalDate day = monday.plusDays(i);
            labels.add(day.getDayOfWeek().toString().substring(0, 3));
            hours.add(raw.getOrDefault(day.toString(), BigDecimal.ZERO));
        }
        int limit = subscription == null ? 6 : Integer.parseInt(String.valueOf(subscription.get("seat_type")).replace("h", ""));
        return Map.of("labels", labels, "hours", hours, "max", limit);
    }

    public Map<String, Object> diary(long userId, String requestedDate) {
        LocalDate date = safeDate(requestedDate);
        Map<String, Object> entry = first("SELECT content, entry_date, updated_at FROM diary_entries WHERE user_id=? AND entry_date=? LIMIT 1", userId, date);
        return entry == null ? Map.of("date", date.toString(), "content", "") : entry;
    }

    @Transactional
    public Map<String, Object> saveDiary(long userId, String requestedDate, String content) {
        LocalDate date = safeDate(requestedDate);
        if (content == null || content.length() > 5000) {
            throw new ResponseStatusException(HttpStatus.UNPROCESSABLE_ENTITY, "Diary entry must be 5,000 characters or fewer.");
        }
        jdbc.update("""
            INSERT INTO diary_entries (user_id, entry_date, content) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE content=VALUES(content), updated_at=CURRENT_TIMESTAMP
            """, userId, date, content.trim());
        return Map.of("status", "success", "date", date.toString());
    }

    public List<Map<String, Object>> paymentHistory(long userId) {
        return jdbc.queryForList("SELECT id, amount, transaction_id, screenshot_path, status, created_at FROM payments WHERE user_id=? ORDER BY id DESC", userId);
    }

    public Map<String, Object> adminDashboard() {
        return Map.of(
            "totalUsers", scalarLong("SELECT COUNT(*) FROM users"),
            "pendingPayments", scalarLong("SELECT COUNT(*) FROM payments WHERE status='pending' OR status='' OR status IS NULL"),
            "activeSubscriptions", scalarLong("SELECT COUNT(*) FROM subscriptions WHERE status='active'"),
            "totalSeats", scalarLong("SELECT COUNT(*) FROM seats"),
            "todayEntries", scalarLong("SELECT COUNT(*) FROM timings WHERE DATE(entry_time)=CURDATE()")
        );
    }

    public List<Map<String, Object>> adminPayments(String status, String search) {
        String normalized = status == null ? "all" : status;
        String term = search == null ? "" : search.trim();
        String sql = "SELECT p.id,p.user_id,p.amount,p.transaction_id,p.utr_no,p.screenshot_path,COALESCE(NULLIF(p.status,''),'pending') AS status,p.created_at,u.name,u.email FROM payments p JOIN users u ON p.user_id=u.id";
        List<Object> args = new ArrayList<>();
        List<String> clauses = new ArrayList<>();
        if (List.of("pending", "success", "rejected").contains(normalized)) {
            clauses.add("COALESCE(NULLIF(p.status,''),'pending')=?"); args.add(normalized);
        }
        if (!term.isBlank()) {
            clauses.add("(u.name LIKE ? OR u.email LIKE ? OR COALESCE(p.transaction_id,'') LIKE ? OR COALESCE(p.utr_no,'') LIKE ?)");
            for (int i = 0; i < 4; i++) args.add("%" + term + "%");
        }
        if (!clauses.isEmpty()) sql += " WHERE " + String.join(" AND ", clauses);
        sql += " ORDER BY CASE WHEN COALESCE(NULLIF(p.status,''),'pending')='pending' THEN 0 ELSE 1 END, p.id DESC";
        return jdbc.queryForList(sql, args.toArray());
    }

    @Transactional
    public Map<String, Object> reviewPayment(long adminId, long paymentId, String action, String planKey) {
        if (!List.of("approve", "reject").contains(action)) {
            throw new ResponseStatusException(HttpStatus.UNPROCESSABLE_ENTITY, "Invalid payment action.");
        }
        if ("reject".equals(action)) {
            int changed = jdbc.update("UPDATE payments SET status='rejected', verified_by_admin=?, verified_at=NOW() WHERE id=? AND (status='pending' OR status='' OR status IS NULL)", adminId, paymentId);
            if (changed != 1) throw new ResponseStatusException(HttpStatus.CONFLICT, "Payment is no longer pending.");
            return Map.of("message", "Payment rejected.");
        }
        Map<String, Object> payment = first("SELECT id,user_id FROM payments WHERE id=? AND (status='pending' OR status='' OR status IS NULL) LIMIT 1", paymentId);
        if (payment == null) throw new ResponseStatusException(HttpStatus.CONFLICT, "Payment is no longer pending.");
        long userId = ((Number) payment.get("user_id")).longValue();
        if (activeSubscription(userId) != null) throw new ResponseStatusException(HttpStatus.CONFLICT, "User already has an active subscription.");
        Map<String, Object> plan = plans().get(planKey);
        if (plan == null) throw new ResponseStatusException(HttpStatus.UNPROCESSABLE_ENTITY, "Invalid subscription plan.");
        LocalDate start = LocalDate.now();
        LocalDate end = start.plusMonths(((Number) plan.get("durationMonths")).longValue()).plusDays(((Number) plan.get("bonusDays")).longValue());
        jdbc.update("UPDATE payments SET status='success', verified_by_admin=?, verified_at=NOW() WHERE id=?", adminId, paymentId);
        jdbc.update("""
            INSERT INTO subscriptions (user_id,plan_name,seat_type,price,duration_months,bonus_days,renewal_type,start_date,end_date,status)
            VALUES (?,?,?,?,?,?,?,?,?,'active')
            """, userId, plan.get("planName"), plan.get("seatType"), plan.get("price"), plan.get("durationMonths"), plan.get("bonusDays"), plan.get("renewalType"), start, end);
        return Map.of("message", "Payment approved and subscription created.");
    }

    public List<Map<String, Object>> adminUsers(String search) {
        String term = search == null ? "" : search.trim();
        String sql = "SELECT u.id,u.name,u.email,u.role,u.phone,u.is_active,u.created_at,(SELECT COUNT(*) FROM subscriptions s WHERE s.user_id=u.id AND s.status='active' AND s.end_date>=CURDATE()) AS active_subscription_count FROM users u";
        if (!term.isBlank()) return jdbc.queryForList(sql + " WHERE u.name LIKE ? OR u.email LIKE ? ORDER BY u.id DESC", "%" + term + "%", "%" + term + "%");
        return jdbc.queryForList(sql + " ORDER BY u.id DESC");
    }

    public List<Map<String, Object>> adminSubscriptions(String status, String search) {
        String sql = "SELECT s.*,u.name,u.email FROM subscriptions s JOIN users u ON u.id=s.user_id";
        List<String> clauses = new ArrayList<>(); List<Object> args = new ArrayList<>();
        if (List.of("active", "expired", "cancelled", "queued").contains(status)) { clauses.add("s.status=?"); args.add(status); }
        if (search != null && !search.isBlank()) { clauses.add("(u.name LIKE ? OR u.email LIKE ? OR s.plan_name LIKE ?)"); for (int i=0;i<3;i++) args.add("%" + search.trim() + "%"); }
        if (!clauses.isEmpty()) sql += " WHERE " + String.join(" AND ", clauses);
        return jdbc.queryForList(sql + " ORDER BY s.id DESC", args.toArray());
    }

    public Map<String, Object> layout() {
        List<Map<String, Object>> sections = jdbc.queryForList("SELECT id,name,section_code,pos_x,pos_y,width,height FROM seat_sections ORDER BY pos_y,pos_x,id");
        List<Map<String, Object>> seats = jdbc.queryForList("SELECT id,seat_no,seat_type,section_name,section_id,position_order,is_active,is_maintenance FROM seats ORDER BY section_name,position_order,id");
        for (Map<String, Object> section : sections) {
            List<Map<String, Object>> children = new ArrayList<>();
            for (Map<String, Object> seat : seats) if (String.valueOf(section.get("section_code")).equals(seat.get("section_name"))) children.add(seat);
            section.put("seats", children);
        }
        return Map.of("sections", sections);
    }

    private Map<String, Object> activeSubscription(long userId) {
        return first("SELECT id,seat_type,start_date,end_date,status FROM subscriptions WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1", userId);
    }
    private List<Map<String, Object>> usageByDay(long userId) { return jdbc.queryForList("SELECT DATE(entry_time) AS day,ROUND(IFNULL(SUM(GREATEST(duration_minutes,0)),0)/60,2) AS hours FROM timings WHERE user_id=? AND MONTH(entry_time)=MONTH(CURDATE()) AND YEAR(entry_time)=YEAR(CURDATE()) GROUP BY DATE(entry_time) ORDER BY day", userId); }
    private List<Map<String, Object>> usageByMonth(long userId) { return jdbc.queryForList("SELECT MONTH(entry_time) AS month_number,ROUND(IFNULL(SUM(GREATEST(duration_minutes,0)),0)/60,2) AS hours FROM timings WHERE user_id=? AND YEAR(entry_time)=YEAR(CURDATE()) GROUP BY MONTH(entry_time) ORDER BY month_number", userId); }
    private Map<String, Object> first(String sql, Object... args) { List<Map<String,Object>> rows=jdbc.queryForList(sql,args); return rows.isEmpty()?null:rows.getFirst(); }
    private long scalarLong(String sql) { Number value=jdbc.queryForObject(sql, Number.class); return value==null?0:value.longValue(); }
    private BigDecimal scalarDecimal(String sql, Object... args) { BigDecimal value=jdbc.queryForObject(sql, BigDecimal.class,args); return value==null?BigDecimal.ZERO:value; }
    private LocalDate safeDate(String value) { try { return value == null || value.isBlank() ? LocalDate.now() : LocalDate.parse(value); } catch (DateTimeParseException exception) { throw new ResponseStatusException(HttpStatus.UNPROCESSABLE_ENTITY, "Date must use YYYY-MM-DD."); } }
    private boolean asBoolean(Object value) { return value instanceof Number number ? number.intValue()!=0 : Boolean.TRUE.equals(value); }
    private Map<String, Map<String, Object>> plans() { return Map.of(
        "6h_monthly", Map.of("planName","1 Month Plan","seatType","6h","price",new BigDecimal("999.00"),"durationMonths",1,"bonusDays",0,"renewalType","normal"),
        "12h_monthly", Map.of("planName","1 Month Plan","seatType","12h","price",new BigDecimal("800.00"),"durationMonths",1,"bonusDays",0,"renewalType","normal"),
        "24h_monthly", Map.of("planName","1 Month Plan","seatType","24h","price",new BigDecimal("1000.00"),"durationMonths",1,"bonusDays",0,"renewalType","normal"),
        "premium_3m", Map.of("planName","3 Month Premium","seatType","6h","price",new BigDecimal("2500.00"),"durationMonths",3,"bonusDays",7,"renewalType","bulk_3month")
    ); }
}
