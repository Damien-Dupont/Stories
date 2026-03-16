import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { ScenePage } from "./pages/ScenePage.tsx";
import "./index.css";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <BrowserRouter>
      <Routes>
        <Route path="/scene/:id" element={<ScenePage />} />
      </Routes>
    </BrowserRouter>
  </StrictMode>,
);
