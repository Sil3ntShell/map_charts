<div class="row justify-content-center p-3">
    <div class="col-lg-12">
        <div class="card custom-card shadow-lg" style="border-radius: 10px; border: 1px solid #007bff;">
            <div class="card-body p-3">
                <div class="row mb-3">
                    <h5 class="text-center mb-2">Estadisticas</h5>
                    <h4 class="text-center mb-2 text-primary">Estadisticas de productos</h4>
                </div>

                <div class="row p-3 justify-content-center">
                   <div class="col-lg-6 rounded border-rounded shadow ">
                        <canvas id="grafico1" width="400" height="200"></canvas>
                   </div>
                   <div class="col-lg-6 rounded border-rounded shadow ">
                        <canvas id="grafico2" width="400" height="200"></canvas>
                   </div>
                </div>
            </div>
        </div>
    </div>
</div>




<script src="<?= asset('build/js/estadistica/index.js') ?>"></script>