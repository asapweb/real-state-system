
# Ajuste de contratos con IPC CREEBBA — **Guía con datos oficiales**

**Fuente oficial:** Planilla **“Serie IPC”** — CREEBBA *(Inflación en Bahía Blanca, Base dic-2020=100; serie empalmada)*.  
**Qué guardar en el sistema:** el **Nivel general** mensual. Los % mensuales se pueden **derivar** de estos niveles.

---

## 1) Tabla de niveles y variación mensual (ene-2023 → jun-2025)

| Mes | Nivel (dic-2020=100) | Variación mensual (%) |
|---|---:|---:|
| 2023-01 | 292,98 | 6.5% |
| 2023-02 | 311,09 | 6.2% |
| 2023-03 | 336,26 | 8.1% |
| 2023-04 | 363,65 | 8.1% |
| 2023-05 | 388,25 | 6.8% |
| 2023-06 | 412,19 | 6.2% |
| 2023-07 | 437,27 | 6.1% |
| 2023-08 | 482,52 | 10.3% |
| 2023-09 | 538,08 | 11.5% |
| 2023-10 | 581,90 | 8.1% |
| 2023-11 | 649,49 | 11.6% |
| 2023-12 | 819,01 | 26.1% |
| 2024-01 | 1.005,15 | 22.7% |
| 2024-02 | 1.151,37 | 14.5% |
| 2024-03 | 1.306,32 | 13.5% |
| 2024-04 | 1.422,97 | 8.9% |
| 2024-05 | 1.507,52 | 5.9% |
| 2024-06 | 1.576,00 | 4.5% |
| 2024-07 | 1.641,58 | 4.2% |
| 2024-08 | 1.713,70 | 4.4% |
| 2024-09 | 1.767,62 | 3.1% |
| 2024-10 | 1.809,62 | 2.4% |
| 2024-11 | 1.859,22 | 2.7% |
| 2024-12 | 1.907,62 | 2.6% |
| 2025-01 | 1.950,69 | 2.3% |
| 2025-02 | 1.997,01 | 2.4% |
| 2025-03 | 2.055,77 | 2.9% |
| 2025-04 | 2.106,51 | 2.5% |
| 2025-05 | 2.139,52 | 1.6% |
| 2025-06 | 2.187,66 | 2.3% |

> Los porcentajes son derivados de los **niveles**: `vm(t) = I(t)/I(t-1) - 1`.

---

## 2) Formas de cálculo (numeradas) y ejemplo práctico
**Contrato de ejemplo:** ARS **1.000.000** — inicia **2024-01** — **ajuste cuatrimestral** (1/5, 1/9, 1/1, 1/5).  
Notación: `I(YYYY-MM)` es el **nivel** del IPC CREEBBA del mes indicado.

### 1) Por tramo (NO acumulativo)
- **Fórmula por ajuste:** `Factor_tramo = I(fin_tramo) / I(inicio_tramo)`  
- **Nuevo alquiler = Alquiler vigente × Factor_tramo`**  
- **Ejemplo:**  
  - 01/05/2024: `I(2024-04)/I(2024-01)`  
  - 01/09/2024: `I(2024-08)/I(2024-04)`  
  - 01/01/2025: `I(2024-12)/I(2024-08)`  
  - 01/05/2025: `I(2025-04)/I(2024-12)`

### 2) Acumulativo desde inicio de contrato
- **Fórmula:** `Factor_acum(t) = I(t) / I(2024-01)`  
- **Nuevo alquiler = 1.000.000 × Factor_acum(t)`**  
- **Observación:** Si aplicás todos los ajustes, **coincide** con el método 1 (salvo redondeos).

### 3) **Acumulado del año (YTD) con base diciembre previo — *Método de la “calculadora CREEBBA”***
- **Fórmula:** `Factor_YTD(Y, t) = I(Y–t) / I((Y–1)–12)`  
- **Qué reporta la calculadora:** el **acumulado anual**. Ej.: **abril 2024 = 73,7%** porque `I(2024-04)/I(2023-12) - 1 ≈ 0,737`.  
- **Uso en contratos:** si se quiere usar YTD para ajustar, lo más consistente es tomar la **forma incremental** (que para tramos termina siendo igual al método 1).

### 4) Interanual (mismo mes vs m−12)
- **Fórmula absoluta:** `I(t) / I(t-12) - 1` (informativa; no usual para fijar alquileres periódicos).  
- **Forma incremental por tramo:** cociente de dos interanuales consecutivos → **se reduce** a `I(fin)/I(inicio_tramo)`.

---

## 3) **Tabla comparativa con importes (contrato ARS 1.000.000)**

| Fecha de ajuste | Método 1 — Tramo (I(fin)/I(inicio_tramo)) | Monto | Método 2 — Acum. desde inicio (I(fin)/I(2024-01)) | Monto | Método 3 — **YTD (dic previo)** (I(fin)/I(dic año previo)) | Monto |
|---|---:|---:|---:|---:|---:|---:|
| 2024-05 | 1.415679 (**+41.57%**) | $1.415.679 | 1.415679 (**+41.57%**) | $1.415.679 | 1.737427 (**+73.74%**) | $1.737.427 |
| 2024-09 | 1.204312 (**+20.43%**) | $1.704.920 | 1.704920 (**+70.49%**) | $1.704.920 | 2.092404 (**+109.24%**) | $2.092.404 |
| 2025-01 | 1.113159 (**+11.32%**) | $1.897.846 | 1.897846 (**+89.78%**) | $1.897.846 | 2.329178 (**+132.92%**) | $2.329.178 |
| 2025-05 | 1.104261 (**+10.43%**) | $2.095.717 | 2.095717 (**+109.57%**) | $2.095.717 | 1.104261 (**+10.43%**) | $1.104.261 |

**Lectura rápida:** para ajustes periódicos, los métodos **1 (Tramo)** y **2 (Acumulado desde inicio)** coinciden. El método **3 (YTD)** reproduce lo que muestra la **calculadora CREEBBA** cuando mirás “acumulado del año” (ej.: **abril 2024 = +73,7%**), que **no** es la misma base que “inicio de tramo” en el primer ajuste del contrato.

---

## 4) Notas de implementación
- Guardar **Niveles** (`index_values.value`) con `effective_date` = día **1** de cada mes.  
- Parametrizar en el tipo de índice (`index_types`) la **regla de base** y si es **acumulativo**.  
- Definir **redondeo** (a pesos enteros) al aplicar cada ajuste.
