import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { ScenePage } from "./pages/ScenePage.tsx";
import { SceneEditPage } from "./pages/SceneEditPage.tsx";
import "./index.css";
import { SceneListPage } from "./pages/SceneListPage.tsx";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <BrowserRouter>
      <Routes>
        <Route path="/scenes/:id" element={<ScenePage />} />
        <Route path="/scenes/:id/edit" element={<SceneEditPage />} />
        <Route path="/scenes" element={<SceneListPage />} />
      </Routes>
    </BrowserRouter>
  </StrictMode>,
);
