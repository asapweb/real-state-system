
# Guía de Ajuste de Alquileres con **IPC CREEBBA** 

**Objetivo.** Explicar, en lenguaje claro, **cómo se actualizan los alquileres** usando el índice **IPC CREEBBA**, cuáles son las **distintas formas de calcular** y **cuál adoptamos** para los contratos. Al final, un **ejemplo real** paso a paso.

**Fuente oficial.** *Planilla “Serie IPC”* del CREEBBA (Índice de Precios al Consumidor, Bahía Blanca). Usamos el **Nivel general** mensual (base dic-2020 = 100, serie empalmada).

---

## 1) La idea central
Para actualizar un alquiler entre dos momentos, miramos **dos valores del índice** y calculamos un **cociente** (un “ratio”):
- **F** = el **mes anterior** a la fecha en que empieza a regir el nuevo alquiler.
- **S** = el **mes base** del tramo (ver métodos más abajo).
- **Factor = I(F) / I(S)**  
- **Nuevo alquiler = Alquiler vigente × Factor**

> Esta forma es **robusta** (sirve aunque cambie la base del índice) y es la que mejor refleja lo pactado en la mayoría de los contratos.

---

## 2) Las 4 formas de calcular (y cuándo se usan)

### 1) **Por tramo** (no acumulativo) → *la que adoptamos*
- **Qué compara:** el mes **F** contra el mes **S** del **tramo** (entre ajustes).
- **Qué resuelve:** “¿Cuánto cambió el índice en este período específico del contrato?”
- **Usos típicos:** ajustes **cuatrimestrales**, **semestrales**, etc.
- **Cómo pedirlo en la calculadora de CREEBBA:** “Desde” el **1° del mes siguiente a S** hasta el **último día de F**.

### 2) **Desde el inicio de contrato** (acumulativo)
- **Qué compara:** siempre contra el **mes de inicio** del contrato.
- **Comentario:** si aplicás cada ajuste en fecha, **termina dando lo mismo** que el método por tramo (salvo redondeo).

### 3) **Acumulado del año (YTD)** — *lo que muestra la calculadora cuando ponés “enero→abril”*
- **Qué compara:** cada mes del año **contra diciembre del año previo**.
- **Comentario:** sirve para **informes de inflación**, **no** para ajustes de tramo, salvo que el contrato lo pida explícitamente.

### 4) **Interanual (m vs m−12)**
- **Qué compara:** un mes **contra el mismo mes** del año anterior.
- **Comentario:** útil en **reportes**; para fijar alquileres periódicos, su versión “incremental” termina siendo igual al **por tramo**.

---

## 3) ¿Qué elegimos y por qué?
Adoptamos **Por tramo (método 1)** porque:
- **Respeta la cláusula:** “ajusta cada X meses por índice” → compara **dos momentos del contrato**.
- **Es justo y transparente:** solo mide el **período pactado**.
- **Es estable:** el cociente *I(F)/I(S)* funciona aunque el organismo cambie la **base**.
- **Sirve para todos los índices:** IPC CREEBBA (mensual) y también UVA/ICL (diarios).

---

## 4) Cómo replicarlo en la **calculadora CREEBBA**
La calculadora compone **variaciones mensuales** entre un “desde” y un “hasta”. Para que te dé **exactamente** *I(F)/I(S)*:
- **Desde:** **1° de (S + 1 mes)**  
- **Hasta:** **último día de F**

> Si ponés “enero→abril”, la calculadora te dará el **acumulado del año (YTD)**, que **no** es el tramo contractual.

---

## 5) Ejemplo paso a paso (contrato realista)
- **Monto inicial:** $1.000.000  
- **Inicio del contrato:** **enero 2024**  
- **Ajuste cuatrimestral:** 01/05/2024 y 01/09/2024  
- **Niveles CREEBBA usados:**  
  - I(2023-12) = **819,01**  
  - I(2024-01) = **1.005,15**  
  - I(2024-04) = **1.422,97**  
  - I(2024-08) = **1.713,70**

### Primer ajuste (rige 01/05/2024) — **Por tramo (adoptado)**
- **F = abril 2024**, **S = enero 2024**  
- **Factor =** 1.422,97 / 1.005,15 = **1.41568** → **+41.57%**  
- **Nuevo alquiler =** $1.000.000 × 1.41568 = **$1.415.679**  
- **En la calculadora de CREEBA:** Desde **01/02/2024** hasta **30/04/2024** (feb + mar + abr).

> **¿Por qué no “enero→abril”?** Porque eso da el **YTD**:  
> 1.422,97 / 819,01 = **1.73743** → **+73.7%** (otra base).

### Segundo ajuste (rige 01/09/2024) — **Por tramo (adoptado)**
- **F = agosto 2024**, **S = abril 2024**  
- **Factor =** 1.713,70 / 1.422,97 = **1.20431** → **+20.43%**  
- **Nuevo alquiler =** $1.415.679 × 1.20431 = **$1.704.919**  
- **En la calculadora de CREEBBA:** Desde **01/05/2024** hasta **31/08/2024** (may + jun + jul + ago).

> **Control cruzado (opcional):** “Desde inicio” en la misma fecha da lo mismo:  
> 1.713,70 / 1.005,15 = **1.70492** → **$1.704.919**.

---

## 6) Guía rápida
1. **Identificá F:** el mes **anterior** al ajuste.  
2. **Elegí S:** el **mes de inicio** del tramo (o el **inicio del contrato** si el cliente quiere “desde inicio”).  
3. **Calculá el factor:** *I(F) / I(S)*.  
4. **Aplicá al alquiler vigente** y redondeá según la política acordada.  
5. **Si querés validar en la calculadora:** “Desde 1° de (S + 1)” hasta “último día de F”.

---

## 7) Resumen ejecutivo
- **Lo que ajusta el alquiler** es el **cambio del índice en el tramo pactado**.  
- Para medir ese cambio, basta con mirar **dos meses**: el **mes base** y el **mes previo al ajuste**.  
- La fórmula es siempre **Factor = I(F)/I(S)**.  
- Esto es **claro, justo y estable**. Y lo podés **verificar** con la calculadora siguiendo el rango correcto.
