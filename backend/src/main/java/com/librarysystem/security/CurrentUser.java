package com.librarysystem.security;

import jakarta.servlet.http.HttpSession;
import java.util.LinkedHashMap;
import java.util.Map;
import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Component;
import org.springframework.web.server.ResponseStatusException;

@Component
public class CurrentUser {
    public static final String SESSION_KEY = "libraryUser";

    @SuppressWarnings("unchecked")
    public Map<String, Object> require(HttpSession session, String requiredRole) {
        Object value = session.getAttribute(SESSION_KEY);
        if (!(value instanceof Map<?, ?>)) {
            throw new ResponseStatusException(HttpStatus.UNAUTHORIZED, "Please log in first.");
        }

        Map<String, Object> user = new LinkedHashMap<>((Map<String, Object>) value);
        if (requiredRole != null && !requiredRole.equals(user.get("role"))) {
            throw new ResponseStatusException(HttpStatus.FORBIDDEN, "You do not have access to this area.");
        }
        return user;
    }

    public long id(HttpSession session, String requiredRole) {
        Object value = require(session, requiredRole).get("id");
        return ((Number) value).longValue();
    }
}
