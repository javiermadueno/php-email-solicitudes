SELECT 
  a.Id,
  a.Fecha_peticion,
  a.Documento_personal,
  a.Tipo_documento,
  a.Nombre,
  a.Apellido1,
  a.Apellido2,
  a.Tipo_via,
  a.Nombre_via,
  a.Numero,
  a.Portal,
  a.Escalera,
  a.Piso,
  a.Puerta,
  a.Localidad,
  a.provincia,
  a.Codigo_postal,
  a.Telefono1,
  a.Telefono2,
  a.Correo_electronico,
  CASE WHEN a.Transferencia = 0 THEN 'FALSE' ELSE 'TRUE' END AS Transferencia,
  b.Estado
FROM
  t_solicitud_duplicado a,
  t_estados_solicitud_duplicado b,
  (SELECT 
    Id_solicitud,
    MAX(Id) maxId 
  FROM
    t_estados_solicitud_duplicado 
  GROUP BY Id_solicitud) e 
WHERE a.Id = b.Id_solicitud 
  AND e.maxId = b.Id 
  AND a.Fecha_peticion >= ?
  AND a.Id NOT IN 
  (SELECT 
    x.Id 
  FROM
    t_solicitud X,
    t_estados_solicitud_duplicado Y
  WHERE x.Id = y.Id_solicitud 
    AND y.Estado IN ('ANULADA')) 
ORDER BY a.Id DESC 
