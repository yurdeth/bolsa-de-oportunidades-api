<style scoped>
.loader {
    width: 100%;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    top: 0;
    left: 0;
    background-color: rgba(0, 0, 0, 0.5);
}

.loader .spinner-border {
    width: 100px;
    height: 100px;
}

/* Título Principal */
.content h1 {
    font-size: 28px;
    color: #8b0000; /* Rojo Oscuro */
    text-align: center;
    margin-bottom: 30px;
}

/* Tarjetas de Estadísticas */
.stats {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    flex: 1;
    min-width: 200px;
    padding: 20px;
    background-color: #ffffff; /* Fondo de las tarjetas */
    border: 2px solid #8b0000; /* Bordes en Rojo Oscuro */
    border-radius: 10px; /* Esquinas redondeadas */
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Sombra ligera */
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: scale(1.02); /* Animación ligera al pasar el cursor */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra más fuerte */
}

/* Iconos */
.stat-card .icon {
    font-size: 30px;
    color: #a11111; /* Rojo Suave */
    margin-bottom: 10px;
}

.stat-card h2 {
    font-size: 16px;
    color: #8b0000; /* Rojo Oscuro */
    margin-bottom: 10px;
    text-transform: uppercase;
    font-weight: bold;
}

.stat-card p {
    font-size: 24px;
    font-weight: bold;
    color: #4b4b4b; /* Texto Principal */
    margin: 0;
}

/* Gráficos */
.details {
    margin-bottom: 40px;
}

.chart {
    flex: 1;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.chart h3 {
    font-size: 18px;
    color: #8b0000;
    margin-bottom: 15px;
}

.chart-placeholder {
    height: 200px;
    background-color: #e9e9e9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4b4b4b;
    font-size: 16px;
}

/* Tabla de Actividades Recientes */
.recent-activities table {
    width: 100%;
    border-collapse: collapse;
}

.recent-activities th,
.recent-activities td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

.recent-activities th {
    background-color: #f7f7f7;
    color: #8b0000;
}

.recent-activities tr:nth-child(even) {
    background-color: #f9f9f9;
}
</style>

<template>
    <div class="loader" v-if="loading">
        <div class="spinner-border text-danger" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
    </div>
    <div v-if="user_data && user_data.id_tipo_usuario == 1">
        <h1>Panel de Administración</h1>

        <!-- Tarjetas de estadísticas principales -->
        <section class="stats">
            <div class="stat-card">
                <i class="fas fa-users icon"></i>
                <h2>Total de Usuarios</h2>
                <p>
                    {{
                        String(Number(totalUsers)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
            <div class="stat-card">
                <i class="fas fa-paper-plane icon"></i>
                <h2>Solicitudes Activas</h2>
                <p>
                    {{
                        String(Number(activeRequests)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle icon"></i>
                <h2>Proyectos Activos</h2>
                <p>
                    {{
                        String(Number(activedProjects)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate icon"></i>
                <h2>Estudiantes Registrados</h2>
                <p>
                    {{
                        String(Number(numStudents)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
        </section>

        <!-- Gráficos y Listados -->
        <section class="row details">
            <div class="col-12 col-md-6 px-3 mb-3">
                <div class="chart">
                    <h3>Usuarios por Rol</h3>
                    <canvas id="userChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="col-12 col-md-6 px-3 mb-3">
                <div class="chart">
                    <h3>Solicitudes por Estado</h3>
                    <canvas id="requestChart" width="400" height="200"></canvas>
                </div>
            </div>
        </section>

        <!-- Sección de Reportes -->
        <section class="reportes mt-5">
            <h2 class="text-center text-danger mb-4">Reportes</h2>
            <div
                class="d-flex justify-content-center align-items-center flex-wrap"
            >
                <button
                    class="btn btn-danger btn-lg m-1"
                    @click="generarReportePDF"
                >
                    <i class="fas fa-file-pdf me-2"></i> Generar Reporte de
                    Proyectos
                </button>
                <button
                    class="btn btn-danger btn-lg m-1"
                    @click="generarReporteAplicaciones"
                >
                    <i class="fas fa-file-pdf me-2"></i> Generar Reporte de
                    Aplicaciones
                </button>
                <button
                    class="btn btn-danger btn-lg m-1"
                    @click="generarReporteEmpresas"
                >
                    <i class="fas fa-file-pdf me-2"></i> Generar Reporte de
                    Empresas
                </button>
            </div>
        </section>
    </div>
    <div v-if="user_data && user_data.id_tipo_usuario == 2">
        <h1>Panel de coordinador</h1>

        <!-- Tarjetas de estadísticas principales -->
        <section class="stats">
            <div class="stat-card">
                <i class="fas fa-paper-plane icon"></i>
                <h2>Solicitudes Activas</h2>
                <p>
                    {{
                        String(Number(activeRequests)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle icon"></i>
                <h2>Proyectos Activos</h2>
                <p>
                    {{
                        String(Number(activedProjects)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate icon"></i>
                <h2>Estudiantes Registrados</h2>
                <p>
                    {{
                        String(Number(numStudents)).replace(
                            /\B(?=(\d{3})+(?!\d))/g,
                            ","
                        )
                    }}
                </p>
            </div>
        </section>

        <!-- Gráficos y Listados -->
        <section class="row details">
            <div class="col-12 col-md-6 px-3 mb-3">
                <div class="chart">
                    <h3>Proyectos por Estado</h3>
                    <canvas
                        id="estudiantesChart"
                        width="400"
                        height="200"
                    ></canvas>
                </div>
            </div>
            <div class="col-12 col-md-6 px-3 mb-3">
                <div class="chart">
                    <h3>Solicitudes por Estado</h3>
                    <canvas id="requestChart" width="400" height="200"></canvas>
                </div>
            </div>
        </section>
    </div>

    <div v-if="user_data && user_data.id_tipo_usuario == 3">
        <h1 class="mt-3">¡Bienvenido a la Bolsa de Oportunidades!</h1>

        <h3 class="mb-2">¡Atención estudiantes!</h3>
        <p class="mb-3">
            Queremos informarles que para disfrutar de todos los beneficios y
            funcionalidades de nuestro servicio, será necesario que descarguen
            nuestra aplicación móvil, disponible tanto para dispositivos Android
            como iOS. A través de esta app, podrán acceder de manera rápida,
            sencilla y segura a todas las herramientas y recursos que hemos
            diseñado especialmente para ustedes.
        </p>

        <h4 class="mb-4">¡Descarga la app y comienza a disfrutar!</h4>
        <div class="d-flex justify-content-center mt-5">
            <a
                href="https://play.google.com/store/apps/details?id=com.fmo.bolsa_oportunidades"
                target="_blank"
                class="btn btn-primary btn-lg"
                style="
                    width: 100%;
                    max-width: 500px;
                    height: 5rem;
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    justify-content: center;
                "
            >
                <i class="fab fa-google-play" style="font-size: 40px"></i>
                Descargar para Android
            </a>
            <a
                href="https://apps.apple.com/us/app/bolsa-de-oportunidades/id1580190134"
                target="_blank"
                class="btn btn-primary ms-2 btn-lg"
                style="
                    width: 100%;
                    max-width: 500px;
                    height: 5rem;
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    justify-content: center;
                "
            >
                <i class="fab fa-apple" style="font-size: 50px"></i> Descargar
                para iOS
            </a>
        </div>
    </div>

    <div v-if="user_data && user_data.id_tipo_usuario == 4">
        <h1>Panel de empresa</h1>
        <h3 class="mt-3">¡Bienvenido a la Bolsa de Oportunidades!</h3>

        <p class="mt-2">
            Este es el lugar donde comienzan las grandes experiencias. Aquí
            encontrarás ofertas de servicio social y pasantías diseñadas para
            potenciar tu desarrollo profesional y personal.
        </p>

        <p class="mt-2">
            Explora las oportunidades, postúlate a las que se alineen con tus
            intereses y da el primer paso hacia tu futuro. ¡El éxito te espera!
        </p>
    </div>
</template>
<script>
import { api } from "../../api";
import Alert from "../../helpers/Alert";
import chart from "../../helpers/Chart";

export default {
    data() {
        return {
            loading: false,
            user_data: JSON.parse(localStorage.getItem("user")),
            totalUsers: 0,
            activeRequests: 0,
            activedProjects: 0,
            numStudents: 0,
        };
    },
    methods: {
        async generarReportePDF() {
            try {
                this.loading = true;
                const response = await api.get("/reporte-proyectos", {
                    responseType: "blob",
                });
                const url = window.URL.createObjectURL(
                    new Blob([response.data])
                );
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "Reporte_Proyectos.pdf");
                document.body.appendChild(link);
                link.click();
                link.remove();
                console.log("Reporte:");
            } catch (error) {
                console.error("Error al generar el reporte:", error);
                Alert("Error", "No se pudo generar el reporte.", "error");
            } finally {
                this.loading = false;
            }
        },
        async generarReporteEmpresas() {
            try {
                this.loading = true;
                const response = await api.get("/reporte-empresas", {
                    responseType: "blob",
                });
                const url = window.URL.createObjectURL(
                    new Blob([response.data])
                );
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "Reporte_Empresas.pdf");
                document.body.appendChild(link);
                link.click();
                link.remove();
                console.log("Reporte de empresas descargado");
            } catch (error) {
                console.error(
                    "Error al generar el reporte de empresas:",
                    error
                );
                Alert(
                    "Error",
                    "No se pudo generar el reporte de empresas.",
                    "error"
                );
            } finally {
                this.loading = false;
            }
        },
        async generarReporteAplicaciones() {
            try {
                this.loading = true;
                const response = await api.get("/reporte-aplicaciones", {
                    responseType: "blob",
                });
                const url = window.URL.createObjectURL(
                    new Blob([response.data])
                );
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "Reporte_Aplicaciones.pdf");
                document.body.appendChild(link);
                link.click();
                link.remove();
                console.log("Reporte de aplicaciones descargado");
            } catch (error) {
                console.error(
                    "Error al generar el reporte de aplicaciones:",
                    error
                );
                Alert(
                    "Error",
                    "No se pudo generar el reporte de aplicaciones.",
                    "error"
                );
            } finally {
                this.loading = false;
            }
        },
    },
    async mounted() {
        this.loading = true;

        try {
            const response = await api.get("/dashboard");
            if (this.user_data && this.user_data.id_tipo_usuario == 1) {
                this.totalUsers = response.data.totalUsers;
                this.activeRequests = response.data.activeRequests;
                this.activedProjects = response.data.activeProjects;
                this.numStudents = response.data.totalStudent;

                // Cargar gráficos
                let dataUserbyRole = response.data.dataUserbyRol,
                    label = dataUserbyRole.map((item) => item.rol),
                    data = dataUserbyRole.map((item) => item.total);
                chart("#userChart", label, data);

                let dataAplicacionesByStatus =
                    response.data.dataAplicacionesByStatus;
                label = dataAplicacionesByStatus.map((item) => item.status);
                data = dataAplicacionesByStatus.map((item) => item.total);
                chart("#requestChart", label, data);
            }

            if (this.user_data && this.user_data.id_tipo_usuario == 2) {
                this.activeRequests = response.data.activeRequests;
                this.activedProjects = response.data.activeProjects;
                this.numStudents = response.data.totalStudent;

                let estudiantes = response.data.dataProyectosbyStatus,
                    label = estudiantes.map((item) => item.rol),
                    data = estudiantes.map((item) => item.total);
                chart("#estudiantesChart", label, data);

                let dataAplicacionesByStatus =
                    response.data.dataAplicacionesByStatus;
                label = dataAplicacionesByStatus.map((item) => item.status);
                data = dataAplicacionesByStatus.map((item) => item.total);
                chart("#requestChart", label, data);
            }
        } catch (error) {
            Alert("Error", error.response.data.message, "error");
            console.error(error);
        } finally {
            this.loading = false;
        }
    },
};
</script>
