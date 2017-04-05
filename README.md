

Una aplicación web tradicional en PHP consta de muchas scripts unificadas por algún
archivo de configuración único y un conjunto de librerías base.

Las aplicaciones web de una sola entrada constan de una script base que debe rutear
a diferentes puntos o rutas según algunos parámetros recibidos.

El despachador es una librería de una sola entrada con lo necesario para rutear y
mostrar vistas elaboradas con PHP. No se incluye ningún extra para manejar los
modelos de datos ya que se prefiere que el usuario elija una propia o use las
abstracciones que PHP ya tiene como PDO.

Se busca con el despachador hacer un micro framework simple y mayormente basado en
el paradigma funcional que facilite las pruebas sobre la aplicación y sobre todo que
reduzca el ~state~ externo que normalmente manejan los controladores en las
aplicaciones tradicionales.

