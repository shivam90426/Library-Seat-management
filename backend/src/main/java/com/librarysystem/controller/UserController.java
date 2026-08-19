package com.librarysystem.controller;

import com.librarysystem.security.CurrentUser;
import com.librarysystem.service.LibraryService;
import jakarta.servlet.http.HttpSession;
import java.util.List;
import java.util.Map;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/user")
public class UserController {
    private final CurrentUser currentUser;
    private final LibraryService library;
    public UserController(CurrentUser currentUser, LibraryService library) { this.currentUser=currentUser; this.library=library; }

    @GetMapping("/dashboard") public Map<String,Object> dashboard(HttpSession session) { return library.userDashboard(currentUser.id(session,"user")); }
    @GetMapping("/seats") public List<Map<String,Object>> seats(HttpSession session) { return library.seatsForUser(currentUser.id(session,"user")); }
    @PostMapping("/seats/{seatId}/book") public Map<String,Object> book(@PathVariable long seatId,HttpSession session) { return library.bookSeat(currentUser.id(session,"user"),seatId); }
    @GetMapping("/my-seat") public Map<String,Object> mySeat(HttpSession session) {
        Map<String,Object> response = new java.util.LinkedHashMap<>();
        response.put("seat", library.mySeat(currentUser.id(session,"user")));
        return response;
    }
    @PostMapping("/timer/start") public Map<String,Object> startTimer(HttpSession session) { return library.startTimer(currentUser.id(session,"user")); }
    @PostMapping("/timer/stop") public Map<String,Object> stopTimer(HttpSession session) { return library.stopTimer(currentUser.id(session,"user")); }
    @GetMapping("/analytics") public Map<String,Object> analytics(HttpSession session) { return library.analytics(currentUser.id(session,"user")); }
    @GetMapping("/weekly-usage") public Map<String,Object> weekly(HttpSession session) { return library.weeklyUsage(currentUser.id(session,"user")); }
    @GetMapping("/diary") public Map<String,Object> diary(@RequestParam(required=false) String date,HttpSession session) { return library.diary(currentUser.id(session,"user"),date); }
    @PostMapping("/diary") public Map<String,Object> saveDiary(@RequestBody Map<String,String> body,HttpSession session) { return library.saveDiary(currentUser.id(session,"user"),body.get("date"),body.getOrDefault("content","")); }
    @GetMapping("/payments") public List<Map<String,Object>> payments(HttpSession session) { return library.paymentHistory(currentUser.id(session,"user")); }
}
