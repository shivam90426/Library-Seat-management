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
@RequestMapping("/api/admin")
public class AdminController {
    private final CurrentUser currentUser;
    private final LibraryService library;
    public AdminController(CurrentUser currentUser, LibraryService library) { this.currentUser=currentUser; this.library=library; }

    @GetMapping("/dashboard") public Map<String,Object> dashboard(HttpSession session) { currentUser.require(session,"admin"); return library.adminDashboard(); }
    @GetMapping("/payments") public List<Map<String,Object>> payments(@RequestParam(defaultValue="all") String status,@RequestParam(required=false) String q,HttpSession session) { currentUser.require(session,"admin"); return library.adminPayments(status,q); }
    @PostMapping("/payments/{paymentId}/review") public Map<String,Object> review(@PathVariable long paymentId,@RequestBody Map<String,String> body,HttpSession session) { return library.reviewPayment(currentUser.id(session,"admin"),paymentId,body.get("action"),body.get("planKey")); }
    @GetMapping("/users") public List<Map<String,Object>> users(@RequestParam(required=false) String q,HttpSession session) { currentUser.require(session,"admin"); return library.adminUsers(q); }
    @GetMapping("/subscriptions") public List<Map<String,Object>> subscriptions(@RequestParam(defaultValue="all") String status,@RequestParam(required=false) String q,HttpSession session) { currentUser.require(session,"admin"); return library.adminSubscriptions(status,q); }
    @GetMapping("/layout") public Map<String,Object> layout(HttpSession session) { currentUser.require(session,"admin"); return library.layout(); }
}
