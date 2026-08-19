package com.librarysystem.controller;

import com.librarysystem.dto.LoginRequest;
import com.librarysystem.dto.RegisterRequest;
import com.librarysystem.security.CurrentUser;
import jakarta.servlet.http.HttpSession;
import java.util.LinkedHashMap;
import java.util.Map;
import org.springframework.dao.DuplicateKeyException;
import org.springframework.http.HttpStatus;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.server.ResponseStatusException;

@RestController
@RequestMapping("/api/auth")
public class AuthController {
    private final JdbcTemplate jdbc;
    private final CurrentUser currentUser;
    private final BCryptPasswordEncoder passwordEncoder = new BCryptPasswordEncoder();

    public AuthController(JdbcTemplate jdbc, CurrentUser currentUser) {
        this.jdbc = jdbc;
        this.currentUser = currentUser;
    }

    @PostMapping("/login")
    public Map<String, Object> login(@RequestBody LoginRequest request, HttpSession session) {
        String email = request.getEmail() == null ? "" : request.getEmail().trim().toLowerCase();
        String role = request.getRole() == null ? "user" : request.getRole().trim();
        if (!role.equals("user") && !role.equals("admin")) {
            throw new ResponseStatusException(HttpStatus.UNPROCESSABLE_ENTITY, "Invalid role.");
        }

        var users = jdbc.queryForList(
            "SELECT id, name, email, password, role FROM users WHERE email=? AND role=? LIMIT 1",
            email, role
        );
        if (users.isEmpty() || request.getPassword() == null
            || !passwordEncoder.matches(request.getPassword(), String.valueOf(users.getFirst().get("password")))) {
            throw new ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid email, password, or role.");
        }

        Map<String, Object> row = users.getFirst();
        Map<String, Object> user = new LinkedHashMap<>();
        user.put("id", ((Number) row.get("id")).longValue());
        user.put("name", row.get("name"));
        user.put("email", row.get("email"));
        user.put("role", row.get("role"));
        session.setAttribute(CurrentUser.SESSION_KEY, user);
        return Map.of("user", user);
    }

    @PostMapping("/register")
    public Map<String, Object> register(@RequestBody RegisterRequest request) {
        String name = request.getName() == null ? "" : request.getName().trim();
        String email = request.getEmail() == null ? "" : request.getEmail().trim().toLowerCase();
        if (name.isBlank() || name.length() > 100 || !email.matches("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$")
            || request.getPassword() == null || request.getPassword().length() < 8) {
            throw new ResponseStatusException(HttpStatus.UNPROCESSABLE_ENTITY, "Enter a name, valid email, and password of at least 8 characters.");
        }
        try {
            jdbc.update(
                "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())",
                name, email, passwordEncoder.encode(request.getPassword())
            );
        } catch (DuplicateKeyException exception) {
            throw new ResponseStatusException(HttpStatus.CONFLICT, "This email is already registered.");
        }
        return Map.of("message", "Registration successful. You can now log in.");
    }

    @GetMapping("/me")
    public Map<String, Object> me(HttpSession session) {
        return Map.of("user", currentUser.require(session, null));
    }

    @PostMapping("/logout")
    public Map<String, Object> logout(HttpSession session) {
        session.invalidate();
        return Map.of("message", "Logged out successfully.");
    }
}
